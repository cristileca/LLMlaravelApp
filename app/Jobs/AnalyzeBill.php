<?php

namespace App\Jobs;

use DateTime;
use Exception;
use App\Models\Bill;
use App\Models\Contract;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use App\Services\LLMOCRService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SimpleXMLElement;

class AnalyzeBill implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bill;
    public $timeout = 400;
    protected $discrepancyOccurrences = [];

    /**
     * Create a new job instance.
     *
     * @param Bill $bill
     */
    public function __construct(Bill $bill)
    {
        $this->bill = $bill;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $billService = new LLMOCRService();

            $this->bill->status = Bill::STATUS_LLM_IN_PROGRESS;
            $this->bill->saveQuietly();

            $contract = $this->bill->contract;

            if (!$contract) {
                $this->updateStatusChanges("Contractul nu a fost gasit!");
                return;
            }
            $annexSummary = $this->extractAnnexSummaries($contract);
            Log::info("sumar anexa", ['annex summary' => $annexSummary]);
            $contractWithAnnexSummary = $this->summarizeContractWithAnnexes($contract->summary, $annexSummary, $billService);
            Log::info("sumar contract", ['contract+annex summary' => $contractWithAnnexSummary]);
            $billAnalysis = $this->analyzeBill($this->bill->contract->summary, $billService);
            Log::info("analiza factura", ['bill analysis' => $billAnalysis]);
            $acceptanceStatus = $this->analyzeAcceptanceStatus($billAnalysis, $billService);
            Log::info("status acceptanta", ['status acceptanta' => $acceptanceStatus]);

            $this->setAcceptanceStatus($acceptanceStatus);
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['bill_id' => $this->bill->id]);
            $this->updateStatusChanges($e->getMessage());
        }
    }

    private function extractAnnexSummaries($contract)
    {
        if (isset($contract->annexes)) {
            $transformedAnnexes = [];
            foreach ($contract->annexes as $index => $annex) {
                if (isset($annex['name']) && isset($annex['summary'])) {
                    $transformedAnnexes[$index] = [
                        'name' => $annex['name'],
                        'date' => $annex['annex_date'],
                        'summary' => $annex['raw_text'],
                    ];
                }
            }
            return $transformedAnnexes;
        }
        return "Nu exista anexe pentru acest contract.";
    }

    private function summarizeContractWithAnnexes($contractSummary, $annexSummary)
    {
        $summarizedContractWithAnnexes = [
            'contract' => [
                'summary' => json_decode($contractSummary)
            ],
            'annexes' => [
                'summary' => $annexSummary
            ]
        ];

        return json_encode($summarizedContractWithAnnexes, JSON_PRETTY_PRINT);
    }

    private function analyzeBill($contractWithAnnexSummary, $billService)
    {
        try {
            $prompt = [
                'model' => 'claude-3-5-sonnet-20240620',
                'max_tokens' => 8000,
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' =>
                                '
O sa iti dau esentialul extras dintr-un contract si anexele acestuia. Va trebui sa verifici o factura, iar te rog sa analizezi toate elementele din factura si sa raspunzi prin intermediul unui JSON care sa contina urmatoarele entitati: numar si serie factura, furnizor, beneficiar, data emiterii in formatul zz/mm/yyyy, valoare si statusul, care poate fi "Aprobat", "De verificat" sau "Refuzat". Concluzia verificarii va fi "Aprobat" atunci cand consideri ca factura este in regula si poate fi platita, "De verificat" atunci cand ceva nu este clar si crezi ca ar fi necesara o verificare manuala, sau "Refuzat" daca ai dovada ca factura nu este in concordanta cu contractul. Daca iti lipsesc date explicite necesare pentru a face verificarea, te rog sa specifici ce lipseste si sa exemplifici ce se regaseste in factura si ce nu este in conformitate cu contractul.
In plus, te rog sa listezi produsele pe care le gasesti atat in factura, cat si in contract, si sa returnezi un JSON care sa contina produsele listate separat, atat cele din contract, cat si cele din factura. In concluzie, te rog sa precizezi daca produsele din factura corespund celor din contract.
De asemenea, compara preturile produselor din factura cu cele din contract si indica eventualele diferente.'
                            ],
                            [
                                'type' => 'text',
                                'text' => "
                            Sumarul contractului:
                            \"\"\"{$contractWithAnnexSummary}\"\"\"
                            "
                            ],
                            [
                                'type' => 'text',
                                'text' => "
                            Textul facturii:
                            \"\"\"{$this->bill->raw_text}\"\"\"
                            "
                            ]
                        ]
                    ]
                ]
            ];
            $decoded_result = runPrompt($prompt, $billService);
            if (!$decoded_result) {
                $this->updateStatusChanges("LLM failed or JSON Decoding failed!");
                return;
            }
            return $decoded_result;
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['bill_id' => $this->bill->id, 'function' => 'analyzeBill']);
            $this->updateStatusChanges($e->getMessage());
            return;
        }
    }

    private function analyzeAcceptanceStatus($formattedAnalysis, $billService)
    {
        try {
            $systemRequirement = "
                ### Language Requirement: ###
                Always respond in Romanian. Translate any extracted information to Romanian if necessary.

                ### Data Usage Restriction: ###
                Use only the information provided within the raw text sections below (enclosed in \"\\\"\\\"\\\"\"). Do not use or infer any data based on external knowledge or previous training data. The analysis should be strictly based on the provided text.

                ### Acceptance Criteria: ###
                1. Assign 'Refuzat' if there are price discrepancies (invoice price is lower than in the contract), quantity discrepancies, products in the invoice not found in the contract, or if the invoice payment term is longer than the contract term.
                2. Assign 'Aprobat' for all other cases, including small differences that do not disadvantage the buyer.
                3. If the payment term in the invoice is shorter than in the contract, disregard it. However, if the contract has a shorter payment term, flag it as an error.

                ### Output Format: ###
                Return the output in the following JSON format:
                {
                    \"acceptance_status\": \"Aprobat\" or \"Refuzat\",
                    \"motiv\": \"Detailed reason for choosing this status in Romanian.\"
                }

                Ensure that the acceptance status and motivation fields are filled correctly based on the analysis, and provide the response strictly in the specified JSON format.
        ";

            // Construct the prompt with the formatted analysis report
            $promptContent = "
                ### ANALYSIS REPORT ###
                \\\"\\\"\\\" " . json_encode($formattedAnalysis, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . " \\\"\\\"\\\"

                ### Example Response ###
                \\\"\\\"\\\"
                {
                    \"acceptance_status\": \"Aprobat/Refuzat\",
                    \"motiv\": \"Motivul pentru care s-a atribuit acest status\"
                }
                \\\"\\\"\\\"

                ### Task: ###
                Please analyze the report above and provide a JSON response in the specified format, ensuring that each field is correctly filled out and structured as requested.
                ";

            $summaryPrompt = [
                'model' => 'claude-3-5-sonnet-20240620',
                'max_tokens' => 8000,
                'temperature' => 0,
                'system' => $systemRequirement,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $promptContent,
                    ]
                ],
            ];

            $result = runPrompt($summaryPrompt, $billService);
            $this->saveAnalysis($result);

            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['function' => 'analyzeAcceptanceStatus']);
            return null;
        }
    }

    private function setAcceptanceStatus($acceptanceStatus)
    {
        switch ($acceptanceStatus->acceptance_status) {
            case 'Refuzat':
                $this->bill->acceptance_status = Bill::STATUS_UNAPPROVED_ALERT;
                break;
            case 'Aprobat':
                $this->bill->acceptance_status = Bill::STATUS_APPROVED;
                break;
            default:
                $this->bill->acceptance_status = Bill::STATUS_CHECK_IF_APPROVED;
                break;
        }

        $this->bill->status = Bill::STATUS_SUCCESS;
        $this->bill->last_status_message = "Documentul a fost analizat si procesat cu succes";
        $this->bill->saveQuietly();
    }

    private function saveAnalysis($result)
    {
        if (isset($result)) {
            $this->bill->analysis = json_encode($result);
        } else {
            $this->bill->analysis = 'Concluziile analizei nu au putut fi extrase/salvate.';
        }

        $this->bill->saveQuietly();

        return $this->bill;
    }

    private function productsInEuro($products, $rateEuro)
    {
        try {
            $arProducts = json_decode($products, true);
            $productsInEuro = [];
            foreach ($arProducts as $product => $price) {
                $priceEuro = round($price / $rateEuro, 2);

                $productsInEuro[$product] = $priceEuro . " euro ";
            }
            $stringAr = json_encode($productsInEuro);
            return $stringAr;
        } catch (Exception $e) {
            Log::error('Eroare la functia productsInEuro: ' . $e->getMessage());
            return "";
        }
    }

    private function updateStatusChanges(string $errorMessage)
    {
        $this->bill->status = Bill::STATUS_FAILED;
        $this->bill->last_status_message = $errorMessage;
        $this->bill->saveQuietly();
        return;
    }
}

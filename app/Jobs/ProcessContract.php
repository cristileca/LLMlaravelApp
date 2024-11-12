<?php

namespace App\Jobs;

use Exception;
use App\Models\Contract;
use App\Models\ContractDetails;
use App\Models\Supplier;
use App\Services\DateService;
use Illuminate\Bus\Queueable;
use App\Services\LLMOCRService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessContract implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contract;
    public $timeout = 400;

    /**
     * Create a new job instance.
     *
     * @param Contract $contract
     */
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $contractService = new LLMOCRService();
        $dateService = new DateService();

        try {
            // OCR

            $fileName = $this->contract->files[0];

            $this->contract->status = Contract::STATUS_OCR_IN_PROGRESS;
            $this->contract->saveQuietly();

            $imagePath = Storage::disk(config('filesystems.default'))->path($fileName);

            // Upload the document to Google Cloud Storage
            $contractService->uploadDocumentToCloudStorage($imagePath, $fileName);

            // Call the OCR method
            $ocrResponse = $contractService->gcsTextract($fileName);  // Aici il scoatem

            // Extract general information

            $data = $this->retrieveGeneraInfo($ocrResponse, $contractService);

            $this->contract->status = Contract::STATUS_LLM_IN_PROGRESS;

            // Extract contract summmary

            $contractSummary = $this->summarizeContract($ocrResponse, $contractService);
            $this->contract->summary = $contractSummary ? json_encode($contractSummary) : null;
            $this->contract->raw_text = $ocrResponse;
            $this->contract->saveQuietly();

            // Database save

            $contract_details = ContractDetails::create(
                [
                    'contract_id' => $this->contract->id ?? 'N/A',
                    'client' => $data->cumparator ?? 'N/A',
                    'objective' => $data->obiectul_contractului ?? 'N/A',
                    'price' => $data->pret ?? 'N/A',
                    'delivery_conditions' => $data->conditii_livrare ?? 'N/A',
                    'penalties' => $data->penalitati ?? 'N/A',
                    'payment_conditions' => $data->conditiile_de_plata ?? 'N/A',
                    'contract_term' => $data->durata ?? 'N/A'
                ]
            );

            $this->contract->details_id = $contract_details->id ?? null;
            $this->contract->contract_number = $data->numar_contract ?? null;

            if (isset($data->data_contractului)) {
                $parsedContractDate = $dateService->parseDateWithMultipleFormats($data->data_contractului);
                $this->contract->issue_date = $parsedContractDate?->format('Y-m-d');
            }

            if (isset($data->data_inceperii_contractului)) {
                $parsedContractStartingDate = $dateService->parseDateWithMultipleFormats($data->data_inceperii_contractului);
                $this->contract->starting_date = $parsedContractStartingDate?->format('Y-m-d');
            }

            if ($this->isSupplierDataComplete($data)) {
                $supplier = Supplier::where('TIN', $data->CUI)
                    ->orWhere('name', $data->nume_furnizor)
                    ->first();

                if (!$supplier) {
                    $supplier = Supplier::create([
                        'name' => $data->nume_furnizor,
                        'TIN' => str_replace(' ', '', trim($data->CUI)),
                        'address' => $data->adresa_furnizor,
                        'trade_register_number' => $data->nr_registrul_comertului,
                        'email' => $data->email,
                        'phone' => $data->telefon,
                        'IBAN' => $data->IBAN
                    ]);
                }

                $this->contract->supplier_id = $supplier->id;
            } else {
                Log::warning("Incomplete client data", ['contract_id' => $this->contract->id, 'data' => $data]);
            }

            $this->contract->cui = $data->CUI ?? "N/A";
            $this->contract->last_status_message = "Document uploaded and processed successfully";
            $this->contract->status = CONTRACT::STATUS_SUCCESS;
            $this->contract->saveQuietly();
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['contract_id' => $this->contract->id]);
        }
    }

    private function retrieveGeneraInfo($textractResponse, $contractService)
    {
        $prompt = [
            'model' => 'claude-3-5-sonnet-20240620',
            'max_tokens' => 8000,
            'temperature' => 0,
            'system' => "You are an expert in analyzing contracts written in Romanian. Always respond in Romanian, even if the instructions are in English. Use only the raw data provided for the contract and do not reference any external sources or information. If any information is missing, set the value to 'null'.",
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "
                        ### Task Description:

                        This is a contract analysis task where you need to extract information using only the given contract text.
                        The contract is in Romanian, and all extracted data should be formatted in JSON.
                        Ensure that no assumptions or inferences are made outside of the provided text.
                        "
                        ],
                        [
                            'type' => 'text',
                            'text' => "
                        ### JSON Template for the Results:

                        \"\"\"
                        {
                        \"numar_contract\": \"12345\",
                        \"data_contractului\": \"dd/mm/yyyy\",
                        \"data_inceperii_contractului\": \"dd/mm/yyyy\",
                        \"nume_furnizor\": \"Supplier name\",
                        \"nume_client\": \"Client name\",
                        \"CUI\": \"Supplier's fiscal code (CUI)\",
                        \"client_CUI\": \"Client's fiscal code (CUI)\",
                        \"adresa_furnizor\": \"Supplier address\",
                        \"adresa_client\": \"Client address\",
                        \"nr_registrul_comertului\": \"Supplier's trade register number\",
                        \"client_nr_registrul_comertului\": \"Client's trade register number\",
                        \"email\": \"Supplier email\",
                        \"client_email\": \"Client email\",
                        \"telefon\": \"Supplier phone number\",
                        \"client_phone_number\": \"Client phone number\",
                        \"IBAN\": \"Supplier IBAN\",
                        \"client_IBAN\": \"Client IBAN\",
                        \"obiectul_contractului\": \"Contract subject details\",
                        \"conditii_livrare\": \"Delivery conditions\",
                        \"penalitati\": \"Penalty details\",
                        \"conditiile_de_plata\": \"Payment conditions\",
                        \"durata\": \"Contract duration\",
                        \"pret\": \"Identify the prices and display their value. If there are multiple prices, show them stacked (separated by a new line) and properly formatted.\"
                        }
                        \"\"\"
                        "
                        ],
                        [
                            'type' => 'text',
                            'text' => "
                        ### Raw Contract Text:

                        \"\"\"{$textractResponse}\"\"\"
"
                        ]
                    ]
                ]
            ]
        ];

        $decoded_result = runPrompt($prompt, $contractService);
        if (!$decoded_result) {
            $this->updateStatusChanges("LLM failed or JSON Decoding failed!");
            return;
        }
        return $decoded_result;
    }



    private function summarizeContract($contractRawText, $contractService)
    {
        // raw text + summary json + summary llmed

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
                            'text' => "
                        Iti voi da continutul unui contract. O sa te rog sa il parcurgi cu atentie si sa extragi din el toate elementele care sunt relevant sa fie verificate in momentul in care vreau sa accept o factura care vine spre aprobare la plata.
                        Extrage-le intr-un mod structurat si usor de consumat. Te rog sa extragi datele foarte detaliat, si sa te gandesti ca fiecare caz pe care il intalnesti in contract s-ar putea sa apara pe o factura, caz in care ar trebui sa il poti regasi. Lucrurile esentiale sunt, preturi, termene de plata, penalitati, valabilitate contract, cont in banca furnizor, detalii furnizor, numar contract, obiectul contractului cu descriere.
                        Extrage informatiile ca JSON, este important sa extragi moneda in care este exprimat pretul si te rog sa fii atent ca jsonul si continutul sa fie in romana.
                        "
                        ],
                        [
                            'type' => 'text',
                            'text' => "
                                Textul contractului:
                                    \"\"\"{$contractRawText}\"\"\"
                        "
                        ]
                    ]
                ]
            ]
        ];

        $result = runPrompt2($prompt, $contractService);

        if (!$result) {
            $this->updateStatusChanges("LLM failed or JSON Decoding failed!");
            return;
        }
        //

        $reverification_prompt = [
            'model' => 'claude-3-5-sonnet-20240620',
            'max_tokens' => 8000,
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "
                                Te rog sa folosesti Json-ul extras din contract. Te rog copiaza Json-ul si modifica fiecare linie la care vezi vreo problema. Foarte important daca faci o modificare comunica acest lucru dupa terminarea jsonului sau comunica ca nu ai facut nici o modificare daca este totul corect.
                                Returneaza rezultatul in acelasi format.
                        "
                        ],
                        [
                            'type' => 'text',
                            'text' => " Dacă nu ai făcut modificări, te rog să returnezi JSON-ul original"
                        ],
                        [
                            'type' => 'text',
                            'text' => "
                                Rezultatul precedent:
                        \"\"\"{$result}\"\"\"
                        "
                        ],
                        [
                            'type' => 'text',
                            'text' => "
                                Textul contractului:
                        \"\"\"{$contractRawText}\"\"\"
                        "
                        ]
                    ]
                ]
            ]
        ];
        $decoded_result = runPrompt2($reverification_prompt, $contractService);
        Log::info("Second step", ['result' => $decoded_result]);
        if (!$decoded_result) {
            $this->updateStatusChanges("LLM failed or JSON Decoding failed!");
            return;
        }
        return $decoded_result;
    }


    private function updateStatusChanges(string $errorMessage)
    {
        $this->contract->status = Contract::STATUS_FAILED;
        $this->contract->last_status_message = $errorMessage;
        $this->contract->saveQuietly();
        return;
    }

    protected function isSupplierDataComplete($data)
    {
        return isset($data->nume_furnizor, $data->CUI, $data->adresa_furnizor, $data->nr_registrul_comertului, $data->telefon, $data->email, $data->IBAN);
    }

    protected function isClientDataComplete($data)
    {
        return isset($data->nume_client, $data->client_CUI, $data->adresa_client, $data->client_nr_registrul_comertului, $data->client_phone_number, $data->client_email, $data->client_IBAN);
    }
}

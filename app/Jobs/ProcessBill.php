<?php

namespace App\Jobs;

use Exception;
use App\Models\Bill;
use App\Models\Contract;
use App\Services\DateService;
use Illuminate\Bus\Queueable;
use App\Services\LLMOCRService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessBill implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bill;
    public $timeout = 400;

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
            $dateService = new DateService();

            $this->bill->status = Bill::STATUS_OCR_IN_PROGRESS;
            $this->bill->saveQuietly();

            $fileName = $this->bill->files[0];
            $imagePath = Storage::disk(config('filesystems.default'))->path('/' . $fileName);

            // Upload the document to Google Cloud Storage
            $billService->uploadDocumentToCloudStorage($imagePath, $fileName);

            $ocrResponse = $billService->gcsTextract($fileName);

            $this->bill->status = Bill::STATUS_LLM_IN_PROGRESS;
            $this->bill->raw_text = $ocrResponse;
            $this->bill->saveQuietly();

            $data = $this->getGeneralInfo($ocrResponse, $billService);

            if (isset($data->detalii)) {
                $jsonData = json_encode($data->detalii, JSON_UNESCAPED_UNICODE);
                $this->bill->details = $jsonData;
            } else {
                $this->bill->details = null;
            }

            $this->setBillDetails($data, $dateService);
            if ($this->bill->contract_id) {
                $this->analyzeBill();
                return;
            }

            $contract = Contract::where('contract_number', $data->numar_contract)->first();

            if ($contract) {
                $this->bill->contract_id = $contract->id;

                $this->analyzeBill();
                return;
            } elseif ($this->bill->seller_cui) {
                $this->findContractWithTheSameCui($this->bill->seller_cui, $data, $billService);
            } else {
                $this->updateStatusChanges("Contractul nu a fost gasit!");
                return;
            }
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['bill_id' => $this->bill->id]);
            $this->updateStatusChanges($e->getMessage());
            return;
        }
    }


    public function getGeneralInfo($textractBill, $billService)
    {
        try {
            $content = "
            ### Task: ###
            You are an artificial intelligence bot that receives an invoice and a contract to analyze and return the requested data as described below, without overcomplicating or including unnecessary details.

            ### Template: ###
            The data I want you to extract are as follows:
            1. **Invoice Number:** Extract the complete invoice number (including any prefixes, if present) and ensure the format is always correct.
            2. **Invoice Date:** Extract the invoice date in 'dd/mm/yyyy' format.
            3. **Due Date of Invoice:** Extract the due date in 'dd/mm/yyyy' format.
            4. **Total Invoice Cost:** Extract the total cost of the invoice (price + VAT). IMPORTANT: Do not include the currency (e.g., RON/EURO), only the number.
            5. **VAT Cost:** Extract the VAT cost of the invoice.
            6. **Contract Number:** Extract the associated contract number.
            7. **Details:** Extract relevant details such as delivery terms, invoice terms, delivery location, number of units, unit of measurement, quantity, unit price (excluding VAT), or any other relevant details.
            8. **Buyer and Seller Information:** Extract details for both buyer and seller:
                - Name,
                - CUI (Fiscal Registration Number),
                - Address,
                - IBAN (choose only one if there are multiple),
                - Bank (choose only one if there are multiple),
                - Phone Number,
                - Email Address.

            ### Attention: ###
            The extracted data should be provided in JSON format.

            ### Example: ###
            <example>
            {
                \"numar_factura\": \"111\",
                \"data_factura\": \"dd/mm/yyyy\",
                \"data_scadenta_factura\": \"dd/mm/yyyy\",
                \"cost_total_factura\": 100,
                \"cost_tva\": 10,
                \"numar_contract\": \"1010\",
                \"date_parti\": {
                    \"vanzator\": {
                        \"nume\": \"Firma SRL\",
                        \"cui\": \"RO83829\",
                        \"adresa\": \"Bucuresti, strada Morii, numarul 10\",
                        \"iban\": \"8938389392\",
                        \"banca\": \"Transilvania\",
                        \"nr_tel\": \"0718274908\",
                        \"email\": \"email@gmail.com\"
                    },
                    \"cumparator\": {
                        \"nume\": \"Firma SRL\",
                        \"cui\": \"RO83829\",
                        \"adresa\": \"Bucuresti, strada Morii, numarul 10\",
                        \"iban\": \"8938389392\",
                        \"banca\": \"Transilvania\",
                        \"nr_tel\": \"0718274908\",
                        \"email\": \"email@gmail.com\"
                    }
                }
            }
            </example>

            ### Behavior: ###
            Ensure the requested data is correctly provided, and that the response is well formatted.

            ### Technique: ###
            If the task is too complex, split it into multiple subtasks.

            ### Observation: ###
            At the end, verify if you have respected the requested structure. If not, reformat it accordingly.
        ";

            $prompt = [
                'model' => 'claude-3-5-sonnet-20240620',
                'max_tokens' => 8000,
                'temperature' => 0,
                'system' => 'The text you will analyze is in Romanian. Use only the provided invoice text and ensure that the output result is in JSON format in Romanian.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "\\\"\\\"\\\" {$content} \\\"\\\"\\\""
                            ],
                            [
                                'type' => 'text',
                                'text' => "This is the extracted text from the invoice: \\\"\\\"\\\"{$textractBill}\\\"\\\"\\\""
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
            Log::error($e->getMessage() . ' function => getGeneralInfo', ['bill_id' => $this->bill->id]);
            $this->updateStatusChanges($e->getMessage());
            return;
        }
    }


    public function matchBillWithContract($contract, $bill, $billService)
    {
        try {
            $instructions = "Analyze the contract and verify if the details from the bill are found in the contract, even if not all contract details are present on the bill. You might have products found in fields like prices, just compare the names found in the contract summary with ones found in the bill.

                Capture the following details for each product/service:
                1. Product/Service Name,
                2. If the product/service is present in the contract (Yes/No).

                Compare the data presented and find a result that will look like the following example and return it as JSON:

                **Example:**
                {
                    \"procent\": 90,
                    \"descriere\": \"The percentage indicates the extent to which the products or services listed in the bill match the contract.\"
                }";

            $billContent = "\"\"\"{$bill}\"\"\"";
            $contractContent = "\"\"\"{$contract}\"\"\"";

            $promptContent = "
                {$instructions}

                ### BILL ###
                This is the extracted text from the bill:
                {$billContent}

                ### CONTRACT ###
                This is the extracted summary from the contract:
                {$contractContent}

                The JSON output should follow this format:
                {
                    \"procent\": \"Percentage of match between the bill and contract (e.g., 90)\",
                    \"descriere\": \"Description explaining the percentage and its implications.\"
                }";

            $prompt = [
                'model' => 'claude-3-5-sonnet-20240620',
                'max_tokens' => 8000,
                'temperature' => 0,
                'system' => 'Do not use any external sources or previous training data—use only the text provided in the sections below. Return the final result in Romanian.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $promptContent
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
            Log::error($e->getMessage() . ' function => matchBillWithContract', ['bill_id' => $this->bill->id]);
            $this->updateStatusChanges($e->getMessage());
            return;
        }
    }




    public function findContractWithTheSameCui($cui, $bill_data, $billService)
    {
        try {
            $statisticData = [];

            $string_bill = json_encode($bill_data, JSON_PRETTY_PRINT);

            Log::info("string_bill", ["bill" => $string_bill]);

            $normalizedCui = strtolower(str_replace(' ', '', $cui));

            $contracts = Contract::join('contract_details', 'contracts.details_id', '=', 'contract_details.id')
                ->whereRaw('LOWER(REPLACE(contracts.cui, " ", "")) = ?', [$normalizedCui])
                ->select(
                    'contracts.id as contract_id',
                    'contracts.contract_number',
                    'contracts.cui',
                    'contracts.summary',
                    'contract_details.id as contract_details_id',
                    'contract_details.*'
                )
                ->get();

            if (count($contracts) < 1) {
                $this->updateStatusChanges("Contractul cu CUI-ul identificat nu a fost gasit!");
                return;
            }
            // exista un contrat cu acelasi cui 
            if (count($contracts) == 1) {
                // $textContract = '';
                // $textContract .= "\n Obiective: \n" . $contracts[0]->objective;
                // $textContract .= "\n Produse/Servicii: \n" . $contracts[0]->price;
                // $textContract .= "\n Conditii de livrare: \n" . $contracts[0]->delivery_conditions;
                // $textContract .= "\n Penalitati \n" . $contracts[0]->penalties;
                // $textContract .= "\n Conditii de plata \n" . $contracts[0]->payment_conditions;
                // $textContract .= "\n Termenul contractului  \n" . $contracts[0]->contract_term;
                $rez = $this->matchBillWithContract($contracts[0]->summary, $string_bill, $billService);
                Log::info("result", ["percent" => $rez->procent, "all" => $rez]);
                if ($rez->procent >= 30) {
                    $this->bill->last_status_message = "Document uploaded and processed succesfully. No analysis made yet";
                    $this->bill->status = Bill::STATUS_IN_PROGRESS;
                    $this->bill->contract_id = $contracts[0]->contract_id;
                    $this->bill->saveQuietly();
                    AnalyzeBill::dispatch($this->bill);
                } else {
                    $this->updateStatusChanges("Contractul nu a putut fi atribuit automat (dupa CIF) unei facturi pt ca similitudinea este de {$rez->procent}%");
                    return;
                }
            }

            // exista mai multe contracte cu acelasi cui
            if (count($contracts) > 1) {
                foreach ($contracts as $contract) {
                    $textContract = '';
                    $textContract .= "\n Obiective: \n" . $contract->objective;
                    $textContract .= "\n Preturi: \n" . $contract->price;
                    $textContract .= "\n Conditii de livrare: \n" . $contract->delivery_conditions;
                    $textContract .= "\n Penalitati \n" . $contract->penalties;
                    $textContract .= "\n Conditii de plata \n" . $contract->payment_conditions;
                    $textContract .= "\n Termenul contractului  \n" . $contract->contract_term;

                    $rez = $this->matchBillWithContract($textContract, $string_bill, $billService);

                    $procent = $rez->procent;
                    $descriere = $rez->descriere;
                    $id_contract = $contract->id;

                    $statisticData[] = [
                        "procent" => $procent,
                        "descriere" => $descriere,
                        "id_contract" => $id_contract
                    ];
                }

                //sortez descrescator dupa procent 
                usort($statisticData, function ($a, $b) {
                    return $b['procent'] <=> $a['procent'];
                });

                // primul contract cu cel mai mare procent
                if ($statisticData[0]['procent'] >= 90) {
                    $this->bill->last_status_message = "Document uploaded and processed succesfully. No analysis made yet";
                    $this->bill->status = Bill::STATUS_IN_PROGRESS;
                    // verify a bit more here!
                    $this->bill->contract_id = $statisticData[0]['id_contract'];
                    $this->bill->saveQuietly();
                    AnalyzeBill::dispatch($this->bill);
                } else {
                    $this->updateStatusChanges("Contractul nu a putut fi atribuit automat (dupa CIF) unei facturi pt ca similitudinea este de {$rez->procent}%");
                    return;
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage() . 'function => findContractWithTheSameCui', ['bill_id' => $this->bill->id]);
            $this->updateStatusChanges($e->getMessage());
            return;
        }
    }

    // Function to start analyzing a bill
    protected function analyzeBill()
    {
        $this->bill->last_status_message = "Document uploaded and processed successfully. No analysis made yet";
        $this->bill->status = Bill::STATUS_IN_PROGRESS;
        $this->bill->saveQuietly();
        AnalyzeBill::dispatch($this->bill);
    }

    private function updateStatusChanges(string $errorMessage)
    {
        $this->bill->status = Bill::STATUS_FAILED;
        $this->bill->last_status_message = $errorMessage;
        $this->bill->saveQuietly();
    }

    protected function setBillDetails($data, $dateService)
    {
        // Seller
        $this->bill->seller_name = $data->date_parti->vanzator->nume ?? null;
        $this->bill->seller_cui = $data->date_parti->vanzator->cui ?? null;
        $this->bill->seller_address = $data->date_parti->vanzator->adresa ?? null;
        $this->bill->seller_IBAN = $data->date_parti->vanzator->iban ?? null;
        $this->bill->seller_bank = $data->date_parti->vanzator->banca ?? null;
        $this->bill->seller_phone_number = $data->date_parti->vanzator->nr_tel ?? null;
        $this->bill->seller_email = $data->date_parti->vanzator->email ?? null;

        // Customer
        $this->bill->customer_name = $data->date_parti->cumparator->nume ?? null;
        $this->bill->customer_cui = $data->date_parti->cumparator->cui ?? null;
        $this->bill->customer_address = $data->date_parti->cumparator->adresa ?? null;
        $this->bill->customer_IBAN = $data->date_parti->cumparator->iban ?? null;
        $this->bill->customer_bank = $data->date_parti->cumparator->banca ?? null;
        $this->bill->customer_phone_number = $data->date_parti->cumparator->nr_tel ?? null;
        $this->bill->customer_email = $data->date_parti->cumparator->email ?? null;

        // VAT
        $this->bill->fee_tva = $data->cost_tva ?? null;

        // Total cost and invoice number
        $this->bill->fee = $data->cost_total_factura ?? null;
        $this->bill->number = $data->numar_factura ?? null;

        // Dates
        if (isset($data->data_factura)) {
            $parsedDate = $dateService->parseDateWithMultipleFormats($data->data_factura);
            $this->bill->date = $parsedDate?->format('Y-m-d');
        }

        if (isset($data->data_scadenta_factura)) {
            $parsedDate = $dateService->parseDateWithMultipleFormats($data->data_scadenta_factura);
            $this->bill->due_date = $parsedDate?->format('Y-m-d');
        }
    }
}

<?php

namespace App\Jobs;

use Exception;
use App\Models\Annex;
use App\Services\DateService;
use App\Services\LLMOCRService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessAnnex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $annex;
    public $timeout = 400;
    /**
     * Create a new job instance.
     */
    public function __construct(Annex $annex)
    {
        $this->annex = $annex;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $dateService = new DateService();
            $annexService = new LLMOCRService();

            $fileName = $this->annex->files;
            $imagePath = Storage::disk(config('filesystems.default'))->path('/' . $fileName);

            $this->annex->status = Annex::STATUS_LLM_IN_PROGRESS;
            $this->annex->saveQuietly();

            // Upload the document to Google Cloud Storage
            $annexService->uploadDocumentToCloudStorage($imagePath, $fileName);

            // Call the OCR method
            $ocrResponse = $annexService->gcsTextract($fileName);

            // Retrieve the annex summary

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
                             Iti voi da continutul unei anexe. O sa te rog sa o parcurgi cu atentie si sa extragi din ea toate elementele care sunt relevante sa fie verificate in momentul in care vreau sa accept o factura care vine spre aprobare la plata.
                            Extrage-le intr-un mod structurat si usor de consumat. Te rog sa extragi datele foarte detaliat, si sa te gandesti ca fiecare caz pe care il intalnesti in contract s-ar putea sa apara pe o factura, caz in care ar trebui sa il poti regasi. Lucrurile esentiale sunt,numele anexei(nume), o decriere detaliata a continutului anexei(descriere), listeaza serviciile/produsele gasite(fiecare serviciu/produs va avea campurile pret(sa contina suma, unitate de masura, tva intr-o linie, daca nu gasesti lasi gol), detalii_aditionale, nume_produs), data anexei (annex_date), termene de plata, penalitati, valabilitate contract, cont in banca furnizor, detalii furnizor, numar contract, obiectul contractului cu descriere.
                            Extrage informatiile ca JSON, este important sa extragi moneda in care este exprimat pretul si te rog sa fii atent ca jsonul si continutul sa fie in romana.
                            "
                            ],
                            [
                                'type' => 'text',
                                'text' => "
                            Textul anexei:
                            \"\"\"{$ocrResponse}\"\"\"
                            "
                            ]
                        ]
                    ]
                ]
            ];

            $this->annex->status = Annex::STATUS_LLM_IN_PROGRESS;
            $this->annex->saveQuietly();

            $decoded_result = runPrompt($prompt, $annexService);
            if (!$decoded_result) {
                $this->updateStatusChanges("LLM failed or JSON Decoding failed!");
                return;
            }

            $this->annex->name = $decoded_result->nume ?? null;
            $this->annex->status = Annex::STATUS_SUCCESS;
            $this->annex->last_status_message = "Document uploaded and processed successfully";
            $this->annex->description = $decoded_result->descriere ?? null;
            $this->annex->summary = json_encode($decoded_result->servicii_produse ?? null);
            $this->annex->raw_text = $ocrResponse;
            $this->annex->annex_date = $decoded_result->annex_date ? $dateService->parseDateWithMultipleFormats($decoded_result->annex_date) : null;
            $this->annex->saveQuietly();

            // Update the contract according to the annex

            $contract_summary = $this->annex->contract->summary;
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
                                Iti voi furniza continutul unei anexe si al sumarului unui contract. Dupa cum stii, anexa poate adauga sau modifica datele existente din contract. Ceea ce imi doresc este sa actualizezi sumarul contractului, fie prin modificarea, fie prin adaugarea informatiilor conform anexei. De exemplu, daca anexa presupune schimbarea unor sume pentru anumite produse, trebuie sa ajustezi sumele din sumarul contractului in functie de aceste modificari.
Te rog sa specifici si numele anexei sau actului aditional care aduce modificarile in sumar. In plus, vreau sa primesc un fisier JSON care sa contina sumarul contractului actualizat, impreuna cu un camp suplimentar care sa indice sursa modificarilor (ex: „modificat prin Act Aditional Nr. 2”).
Este important sa te asiguri ca toate datele din anexele anterioare sunt pastrate si nu suprascrise. Verifica atent daca trebuie adaugate sau modificate elemente in functie de noile anexe, astfel incat sumarul final sa includa toate informatiile relevante din anexele adaugate.
                                "
                            ],
                            [
                                'type' => 'text',
                                'text' => "Textul anexei: \"\"\"{$ocrResponse}\"\"\""
                            ],
                            [
                                'type' => 'text',
                                'text' => "Sumarul contractului: \"\"\"{$contract_summary}\"\"\""
                            ]
                        ]
                    ]
                ]
            ];

            $decoded_result = runPrompt($prompt, $annexService);

            if (!$decoded_result) {
                $this->updateStatusChanges("LLM failed or JSON Decoding failed!");
                return;
            }

            $newSummary = json_encode($decoded_result) ?? null;

            if ($newSummary) {
                $this->annex->contract->summary = $newSummary;
                $this->annex->contract->saveQuietly();
            } else {
                $this->updateStatusChanges("No changes detected in the summary.");
            }
        } catch (Exception $e) {
            Log::error('Job failed with exception', ['exception' => $e->getMessage()]);
        }
    }

    private function updateStatusChanges(string $errorMessage)
    {
        $this->annex->status = Annex::STATUS_FAILED;
        $this->annex->last_status_message = $errorMessage;
        $this->annex->saveQuietly();
        return;
    }
}

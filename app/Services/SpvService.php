<?php

namespace App\Services;


use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;

class SpvService
{

    private $folderName;
    private $clientAPIANAF;

    public function __construct()
    {
        $this->clientAPIANAF = new Client();

        $this->folderName = 'billsSpv';

        if (!Storage::disk(config('filesystems.default'))->exists($this->folderName)) {
            Storage::disk(config('filesystems.default'))->makeDirectory($this->folderName);
        }
    }


    public function openAiCallXmlToHtml($xml)
    {
        $apiKey = config('services.openAiKey');
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $client = new Client([
            "base_uri" => $endpoint,
            "headers" => [
                'Content-Type' => "application/json",
                "Authorization" => 'Bearer ' . $apiKey
            ]
        ]);


        $dataXmlToHtml = [
            "model" => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            "type" => 'text',
                            'text' => '
                                \n Task: Tu esti un bot de inteligenta artificiala care primeste un document xml care reprezinta o factura, iar tu trebuie sa imi returnezi documentul in format html.
                                \n File content: ' . $xml . '
                                \n Traducere in romana: Datele sa vina traduse in limba romana fara diacritice
                                \n Important: rezultatul final sa fie doar continut html despre factura, fara alte stringuri, si pune corect tagurile HTML
                                \n Tehnica: Daca cerinta este prea compleza imparte-ti lucrul de facut si fa-le pe rand.
                                '
                        ],

                    ]
                ]
            ],
        ];


        try {
            $response = $client->post('', ['json' => $dataXmlToHtml]);
            $body = $response->getBody();
            $content = json_decode($body, true);
            return $content['choices'][0]['message']['content'];
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }


    public function xmlToPdfCustom($xml, $pathToPdf)
    {
        try {

            $dompdf = new Dompdf();
            $html = $this->openAiCallXmlToHtml($xml);

            $dompdf->loadHtml($html);
            $dompdf->render();

            $pdfOutput = $dompdf->output();
            file_put_contents($pathToPdf, $pdfOutput);

        } catch (Exception $e) {
            throw new Exception($e);
        }

    }

    //function to filter all the bills that are approved in SPV
    function readPagesBills($nrPag)
    {
        $startTime = 1723000000000;
        $endTime = 1723032820252;
        $cif = config('services.cuiBusiness');
        $filtru = 'T';
        $spvApiUrlCustom = config('services.spvApiUrlCustom');

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer 1'])->get(
                $spvApiUrlCustom . '/listaMesajePaginatieFactura',
                [
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'cif' => $cif,
                    'pagina' => $nrPag,
                    'filtru' => $filtru
                ]
            );

            $data = json_decode($response->getBody(), true);

            if (isset($data['mesaje']) && is_array($data['mesaje'])) {

                $totalPages = $data['numar_total_pagini'];
                $indexCurrentPage = $data['index_pagina_curenta'];

                $this->interpretArWithBills($data['mesaje']);

                for ($i  = $nrPag + 1; $i <= $totalPages; $i++) {
                    $this->readPagesBills($i);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }


    public function xmlToPdfAnaf($xml, $pathToPdf)
    {
        $isFinal = false;
        try {
            $url = 'https://webservicesp.anaf.ro/prod/FCTEL/rest/transformare/FACT1';
            $client = new Client();
            $response = $client->request('POST', $url, [
                "headers" => ["Content-Type" => "text/plain"],
                'body' => $xml,
                'sink' => $pathToPdf
            ]);

            $isFinal = true;
        } catch (Exception $e) {
            $isFinal = false;
        }

        return $isFinal;
    }


    public function interpretArWithBills($bills)
    {
        // for each bill on every page, get its ID to download it as a zip folder
        foreach ($bills as $bill) {

            $idBill = $bill['id'];
            $idSolicitare = $bill['id_solicitare'];
            $url = config('services.spvApiUrlCustom') . '/descarcare';

            $stream = Storage::disk('local')->put($this->folderName . '/'  . $idBill . '.zip', '');
            $pathFolderSpv = Storage::disk('local')->path($this->folderName);
            $pathZip = $pathFolderSpv . '/' . $idBill . '.zip';

            try {
                $response = $this->clientAPIANAF->request('GET', $url, [
                    'query' => [
                        'id' => $idBill,
                    ],
                    'headers' => [
                        "Authorization" => 'Bearer 1'
                    ],

                    'sink' => $pathZip
                ]);

                //unzip
                $zip = new \ZipArchive;
                $res = $zip->open($pathZip);
                $pathFolderUnzip = $pathFolderSpv . '/' . $idBill;
                if ($res === TRUE) {

                    $zip->extractTo($pathFolderUnzip);
                    $zip->close();
                } else {
                    throw new Exception('failed, code:' . $res);
                }

                // delete zip folder  
                unlink($pathZip);

                $pathXML = $pathFolderUnzip . '/' . $idSolicitare . '.xml';
                $data = file_get_contents($pathXML);
                $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $data);

                //delete folder with xmls (content is in $xml)
                array_map('unlink', glob("$pathFolderUnzip/*.*"));
                rmdir($pathFolderUnzip);

                $pathToPdf = $pathFolderSpv . '/' . $idSolicitare . '.pdf';
                $isDowloaded = $this->xmlToPdfAnaf($xml, $pathToPdf);

                // if the API from ANAF dosn't work, we call our custom function
                if (!$isDowloaded) {
                    $this->xmlToPdfCustom($xml, $pathToPdf);
                }
            } catch (Exception $e) {
                throw new Exception($e);
            }
        };
    }
}





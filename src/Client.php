<?php

namespace ProScholy\LilypondRenderer;

use GuzzleHttp\Client as HttpClient;
// // use GuzzleHttp\Exception\RequestException;
// // use GuzzleHttp\Exception\ClientException;

use Exception;

class Client
{
    protected $client;

    public function __construct()
    {
        $this->client = new HttpClient([
            'base_uri' => config('lilypond_renderer.host') . ':' . config('lilypond_renderer.port')
        ]);
    }

    public function render($lilypond_src, $recipe) : RenderResult
    {
        // todo: catch exceptions 

        $response = $this->client->post("make?recipe=$recipe", [
            'multipart' => [
                [
                    'name'     => 'file_lilypond', // input name, needs to stay the same
                    'contents' => (string)$lilypond_src,
                    'filename' => 'score.ly' // doesn't matter
                ]
            ]
        ]);

        return new RenderResult($recipe, json_decode($response->getBody()->getContents()));
    }

    public function renderSvg($lilypond_src, $crop = true) : RenderResult
    {
        return $this->render($lilypond_src, $crop ? 'svgcrop' : 'svg');
    }

    public function getProcessedFile($tmp, $filename) : string
    {
        $response = $this->client->get("get?dir=$tmp&file=$filename");

        return $response->getBody()->getContents();
    }

    public function getResultLog(RenderResult $res) : string
    {
        return $this->getProcessedFile($res->getTmp(), 'log.txt');
    }

    public function getResultOutputFile(RenderResult $res) : string
    {
        if (!$res->isSuccessful()) {
            throw new Exception("The result was unsuccessful, cannot get the final output file.");
        }

        return $this->getProcessedFile($res->getTmp(), $res->getRecipeOutputFile());
    }

    public function deleteResult(RenderResult $res) : bool
    {
        $promise = $this->deleteResultAsync($res);
        $response = $promise->wait();

        $success = $response->getBody()->getContents() == "ok\n";

        if ($success) {
            $res->markAsDeleted();
        }

        return $success;
    }

    public function deleteResultAsync(RenderResult $res)
    {
        $tmp = $res->getTmp();
        return $this->client->getAsync("del?dir=$tmp");
    }
}


//     // private function request(string $method, string $endPoint, array $params = [])
//     // {
//     //     try {
//     //         $response = $this->client->request($method, $endPoint, $params);
//     //     } catch (RequestException $ex) {
//     //         $resp = $ex->getResponse();
//     //         $data = json_decode($resp->getBody()->getContents());

//     //         throw new ERPApiException($data->status . ' (' . $resp->getStatusCode() . ')', $resp->getStatusCode());
//     //     }

//     //     // other possible exceptions: GuzzleHttp\Exception\ServerException (504: timed out)
//     //     // but those should be catched in the app...I guess? 

//     //     return json_decode($response->getBody()->getContents());
//     // }

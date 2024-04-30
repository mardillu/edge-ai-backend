<?php
namespace App\Http\Controllers;

use GeminiAPI\Client;
use GeminiAPI\Enums\HarmBlockThreshold;
use GeminiAPI\Enums\HarmCategory;
use GeminiAPI\Enums\Role;
use GeminiAPI\GenerationConfig;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\SafetySetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\UssdStep;
use HaoZiTeam\ChatGPT\V2 as ChatGPTV2;
use HaoZiTeam\ChatGPT\V1 as ChatGPTV1;
use Stichoza\GoogleTranslate\GoogleTranslate;
use DateTime;

class UssdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        /**
         if(!in_array($request->MSISDN, [233550670914, 233282077202, 233207603335])){
         return response()->json([
         "USERID" => "research",
         "MSISDN" => $request->MSISDN,
         "USERDATA" => $request->USERDATA,
         "MSG" => "You are not allowed to access this service at this time",
         "MSGTYPE" => false
         ], 200);
         }
         **/

        $lastUssdStep = UssdStep::where('msisdn', $request->MSISDN)
            ->latest()
            ->first();
        $secondsDifference = 60;
        if ($lastUssdStep)
        {
            $givenDate = new DateTime($lastUssdStep->updated_at);
            $currentDate = new DateTime();
            $secondsDifference = $currentDate->getTimestamp() - $givenDate->getTimestamp();
        }

        if (!$lastUssdStep || $lastUssdStep->completed || ($lastUssdStep->step_zero === 'PASS' && str_contains($request->USERDATA, '920')) || ($lastUssdStep->step_two && str_contains($request->USERDATA, '920') && $secondsDifference > 45))
        {
            //create
            $source = 'USSD';
            if($request->SOURCE){
            $source = $request->SOURCE;
            }
            UssdStep::create(['msisdn' => $request->MSISDN, 'step_zero' => 'PASS', 'source' => $source]);
            //return
            return response()->json([
                    "USERID" => "research",
                    "MSISDN" => $request->MSISDN,
                    "USERDATA" => $request->USERDATA,
                    "MSG" => "Welcome to the Edge!\nAsk me anything in your language...",
                    "MSGTYPE" => true
                ], 200);
        }
        else
        {
            //find step
            if ($lastUssdStep->step_two)
            {
                $newPageNumber = $lastUssdStep->page + 1;
                if (($request->USERDATA === '3') && $lastUssdStep->page > 1)
                {
                    $newPageNumber = $lastUssdStep->page - 1;
                }
                elseif ($request->USERDATA === '1')
                {
                    $newPageNumber = $lastUssdStep->page + 1;
                }
                else
                {
                    if (str_contains($request->USERDATA, '920'))
                    {
                        $newPageNumber = $lastUssdStep->page + 1;
                    }
                    else
                    {
                        $nav = '<br>1: Next<br>3: Back';
                        if ($newPageNumber == 1)
                        {
                            $nav = '<br>1: Next';
                        }
                        return response()->json([
                            "USERID" => "research",
                            "MSISDN" => $request->MSISDN,
                            "USERDATA" => $request->USERDATA,
                            "MSG" => "Invalid input! $nav",
                            "MSGTYPE" => true
                        ]);
                    }
                }
                $pageContent = $this->getPageContent($lastUssdStep->step_two, $newPageNumber);
                $lastUssdStep->update(['page' => $newPageNumber]);

                $pageInResult = mb_substr($lastUssdStep->step_two, (mb_strlen($pageContent) * -1));
                $pageIsDifferent = $pageContent !== $pageInResult && trim($pageContent) !== '';
                if ($pageIsDifferent)
                {
                    if ($newPageNumber == 1)
                    {
                        $pageContent = $pageContent . '<br>1: Next';
                    }
                    else
                    {
                        $pageContent = $pageContent . '<br>1: Next<br>3: Back';
                    }
                }
                else
                {
                    $lastUssdStep->update(['completed' => 1]);
                }
                return response()
                    ->json([
                        "USERID" => "research",
                        "MSISDN" => $request->MSISDN,
                        "USERDATA" => $request->USERDATA,
                        "MSG" => $pageContent,
                        "MSGTYPE" => $pageIsDifferent
                    ], 200);
            }
            elseif ($lastUssdStep->step_zero === 'PASS')
            {
            if(str_contains($request->USERDATA, '920')){
            $lastUssdStep->update(['completed' => 1]);
            return response()->json([
                    "USERID" => "research",
                    "MSISDN" => $request->MSISDN,
                    "USERDATA" => $request->USERDATA,
                    "MSG" => "Oops, that did not work. Try again",
                    "MSGTYPE" => false
                ], 200);
            }
                $lastUssdStep->update(['step_one' => 'PASS']);


                $history = [
                    Content::text("You are a helpful AI assistant that responds to users' prompts in a short simple paragraph in the language of the prompt", Role::User),
                ];

                $safetySetting = new SafetySetting(
                    HarmCategory::HARM_CATEGORY_HATE_SPEECH,
                    HarmBlockThreshold::BLOCK_LOW_AND_ABOVE,
                );
                $generationConfig = (new GenerationConfig())
                    ->withCandidateCount(1)
                    ->withMaxOutputTokens(40)
                    ->withTemperature(0.5)
                    ->withTopK(40)
                    ->withTopP(0.6);

                $client = new Client(env('GEMINI_API_KEY', ''));
                $chat = $client->geminiPro()
                    ->withAddedSafetySetting($safetySetting)
                    ->withGenerationConfig($generationConfig)
                    ->startChat()
                    ->withHistory($history);

                $response = $chat->sendMessage(new TextPart($request->USERDATA));
                $final_result = $response->text();

                $lastUssdStep->update(['step_two' => $response->text(), 'page' => 1]);

                $pageContent = $this->getPageContent($final_result, 1);

                $pageInResult = mb_substr($final_result, (mb_strlen($pageContent) * -1));
                $pageIsDifferent = $pageContent !== $pageInResult && trim($pageContent) !== '';
                if ($pageContent !== $pageInResult)
                {
                    $pageContent = $pageContent . '<br>1: Next';
                }
                else
                {
                    $lastUssdStep->update(['completed' => 1]);
                }
                return response()->json([
                        "USERID" => "research",
                        "MSISDN" => $request->MSISDN,
                        "USERDATA" => $request->USERDATA,
                        "MSG" => $pageContent,
                        "MSGTYPE" => $pageIsDifferent
                    ], 200);
            }
        }

        return response()->json([
            "USERID" => "research",
            "MSISDN" => $request->MSISDN,
            "USERDATA" => $request->USERDATA,
            "MSG" => "Edge AI is unable to handle your request at this time",
            "MSGTYPE" => false
        ], 200);
    }

    private function getPageContent($response = '', $page = 1)
    {
        if ($response == '')
        {
            return '';
        }
        $beginIndex = 0;
        $pageContent = '';
        for ($i = 0;$i < $page;$i++)
        {
            $pageContent = mb_substr($response, $beginIndex, 128);

            // Remove Last Word from String
            if (mb_strlen($pageContent) > 64)
            {
                $pageContent = mb_substr($pageContent, 0, mb_strrpos($pageContent, " "));
            }

            $beginIndex += mb_strlen($pageContent);
        }
        return $pageContent;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //

    }
}

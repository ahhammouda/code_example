<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ArticleEN;
use App\ArticleAR;
use App\User;
use App\Rule;
use App\Metadata;
use App\Suggestion;
use App\Recommendation;
use Illuminate\Support\Facades\DB;

use App\Jobs\PythonAnalysisJob;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SuggestionsController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        //get all suggestions
        $suggestions = Suggestion::all()->take(100);
        return view('suggestions', compact('suggestions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('addsuggestion');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //create a new suggestion, assign values from request and save it
        $testSuggestion = Suggestion::where('originalText', $request->original_text)->first();
        if ($testSuggestion) {
            //it exists so i dont want to add it to the db
        } else {
            //create and save
            $suggestion = new Suggestion();
            $suggestion->suggestionText = $request->suggestion_text;
            $suggestion->originalText = $request->original_text;
            $suggestion->save();
        }
        $suggestions = Suggestion::all()->take(100);
        return view('suggestions', compact('suggestions'));

    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $suggestion = Suggestion::find($id);
        return view('editsuggestion', compact('suggestion'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $suggestion = Suggestion::find($id);
        $suggestion->suggestionText = $request->suggestion_text;
        $suggestion->originalText = $request->original_text;
        $suggestion->save();

        $suggestions = Suggestion::all()->take(100);
        return view('suggestions', compact('suggestions'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //find a suggestion, delete it, and then return to the last page with notification
        $suggestion = Suggestion::find($id);
        $suggestion->delete();
        $suggestions = Suggestion::all()->take(100);
        return view('suggestions', compact('suggestions'));
      
    }


    /*
    * SV- Part of Controller that handles pages accessible for users without an account.
    * (To be connected to AI Siha login later.)
    * 
    * Functions: 
        (1) list selection of articles,
        (2) allow for making suggestions by clicking on words
        (3) receive & store recommendations similar to suggestions through ajax calls
    */

    public function listArticles($category = 'Health A-Z', $letter = 'أ')
    {
        switch ($category) {
            case 'health':
                $categori = 'Health A-Z';
                break;
            case 'medicines':
                $categori = 'Medicines';
                break;
            case 'livewell':
                $categori = 'Live well';
                break;
            case 'mentalhealth':
                $categori = 'Mental Health';
                break;
            case 'pregnancy':
                $categori = 'Pregnancy';
                break;
            default:
            $categori = 'Health A-Z';
            break;
        }
        $articles = ArticleAR::where('ar_letter', $letter)->where('category', $categori)->get();
        $userId = \Auth::user()->id;

        //for each article count number of suggestions for logged in user
        foreach ($articles as $article)
        {
            $count = Suggestion::where('userId', $userId)->where('articleID' ,'=', $article->id)->count();
            $article->count = $count;
        }
        return view('listArticles', compact('articles', 'letter', 'category'));
    }
    
    public function sotrearticle(Request $request)
    {
        $htmltext = $request->result;
        $articleId = $request->articleId;

        $article = ArticleAR::where('id', $articleId)->first();
        $article->article = $htmltext;
        if ($article->save()) {
            $articleWords = Metadata::where('articleId', $articleId)->delete();
            $result = $this->fillmetadata($htmltext, $articleId);
            if ($result) {
                return "true";
            }else {
                return "false";
            }
        }      
        
    }

    public function saveEnglishArticle(Request $request){
        // use code from above?
        $htmltext = $request->result;
        $articleId = $request->articleId;

        $article = ArticleEN::where('id', $articleId)->first();
        $article->article = $htmltext;
        $article->isTranslated = 69;
        $article->save();
        return "true";
    }

    public function fillmetadata($articletext, $articleId){

        try {

            $articletext = str_replace("  ", " ", $articletext);
            $articletext = str_replace("</", "+END+</", $articletext);
            $articletext = str_replace("<", " <", $articletext);
            $articletext = str_replace('style=";text-align:right;direction:rtl"', "", $articletext);
            $articletext = str_replace(">", "> ", $articletext);
            $articletext = str_replace(" >", ">", $articletext);
            $articletext = str_replace("  ", " ", $articletext);

            // get text sentence per sentence, first split on beginning tag <, then split all on end tag >
            $sentences = explode("+END+", $articletext);

            // reset sentence number for each article
            $sentencekey = 1;
            $wordNr = 1;            

            foreach ($sentences as $sentence) {

                // tags may contain spaces, protect them from being split like words
                // first search for the tags with spaces
                $sentenceItems = explode(" ", $sentence);
                if (count($sentenceItems) > 1) {

                    // there can be multiple tags with spaces in the same sentence
                    foreach ($sentenceItems as $key => $item) {
                        try{
                            // splitting fails if tags get broken (they have beginning tag but no end)
                            if ($item != "" && ($item[0] == "<") && !strpos($item, ">")) {

                                // get tag from beginning (item) to end (>) and use part of tag after space
                                $regex = '/' . $item . '(.*?)>/s';
                                preg_match($regex, $sentence, $brokenTag);

                                // convert space to unique code for protection
                                $tagProtected = str_replace(" ", "_SPACE_", $brokenTag[1]);
                                $fullTagProtected = $item . $tagProtected . ">";
                                $sentence = preg_replace($regex, $fullTagProtected, $sentence);
                            }

                        // indexing fails -> pass
                        } catch (\Exception $e) {
                            return false;
                        }                       
                    }  
                }

                // since all tags with spaces are protected, we can split text into words
                $words = explode(" ", $sentence);

                foreach ($words as $word) {

                    // put back space after splitting
                    $word = str_replace("_SPACE_", " ", $word);

                    // save word in metadata
                    if ($word) {
                        $metadata = new Metadata();
                        $metadata->word = $word;
                        $metadata->articleId = $articleId;
                        $metadata->wordNr = $wordNr;

                        // end of sentence means end tag occurs right after a word (code 1)
                        if (str_contains($word, "</") && $code == 1) {
                            $sentencekey = $sentencekey + 1;
                            $wordNr = 1;
                        }
                        // tags get code 0, words code 1 (this also keeps track of previous code)
                        if (str_contains($word, "<")) {
                            $code = 0;

                        // new word, increase wordcount
                        } else {
                            $code = 1;
                            $wordNr = $wordNr + 1;
                        }
                        $metadata->code = $code;
                        $metadata->sentenceNr = $sentencekey;
                        $metadata->save();
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
       
    }

    public function editArticle($id){
        $article = ArticleAR::where('id', $id)->first();
        $articleEN = ArticleEN::where('id', $id)->first();

        return view('editarticle', compact('article', 'articleEN'));

    }
    

    public function showArticle($id)
    {
        // regenerate article from metadata
        $article = ArticleAR::where('id', $id)->first();
        $articleEN = ArticleEN::where('id', $article->enID)->first();
        $plainArticle = $article->article;

        $articleWords = Metadata::where('articleId', $id)->get();

        //get the words of the same sentence in a collection
        $uniqueSentences = $articleWords->unique('sentenceNr');
        
        //the final result will be stored in this variable as an html text
        $finalArticle = "";

        // we know how many sentence we have 
        $numberOfSentences = $uniqueSentences->count();
        for ($i=1; $i < $numberOfSentences + 1; $i++) { 

            //get the word of the first, (then second etc ..) sentence
            $words = $articleWords->where('sentenceNr', $i);

            // reconstruct article
            foreach ($words as $meta) {

                // tags do not get span but should be used for article structure
                if ($meta->code == 0 && !(str_contains($meta->word, "href") || str_contains($meta->word, "</a"))) {
                    $finalArticle .= $meta->word;

                // add span tag and a space in the end of each word
                } elseif ($meta->code == 1) {

                    // unique id 
                    $wordId = $meta->articleId . "_" . $meta->sentenceNr . "_" . $meta->wordNr;

                    // word with span tag and data
                    $spannedWord = '<span class="clickable" id="' . $wordId . '" article-id="' . $meta->articleId . '" word-position="' . $meta->wordNr . '" sentence="' . $meta->sentenceNr . '" >' . $meta->word . '</span>' . ' ';
                    
                    // mark word in text if it has suggestion
                    // if ($meta->status != 0) {
                    if ($meta->status2 != 'no_suggestions') {

                        // user suggestion
                        // if ($meta->status == 2) {
                        if ($meta->status2 == 'not_accepted') {
                            $markType = 'user';
                            $markColor = '#bbdefb'; // blue

                        // accepted suggestion
                        // } elseif ($meta->status == 1) {
                        } elseif ($meta->status2 == 'accepted') {
                            $markType = 'accepted';
                            $markColor = '#c5e1a5'; // green

                            // find most accepted suggestion and replace original word by that word
                            $sameSuggestions = Suggestion::where('sentence', $meta->sentenceNr)->where('articleID', $meta->articleId)->where('wordPosition', $meta->wordNr)->get();        
                            $suggestionCount = $sameSuggestions->countBy('suggestionText');
                            $suggestionCount = json_decode(json_encode($suggestionCount), true); // array
                            arsort($suggestionCount); // ranked array
                            $keys = array_keys($suggestionCount); // keys are the words, values are the votes
                            $newWord = $keys[0];
                            $spannedWord = str_replace($meta->word, $newWord, $spannedWord);

                        // computer suggestion
                        } else {

                            // TODO: replace word in text here as well
                            $markType = 'computer';
                            $markColor = '#ffebee'; // red
                        }
                        
                        // replace original word to most accepted word
                        // if ($meta->status == 1 | $meta->status == 3) {
                        //     $newWord = 
                        //     $spannedWord = str_replace($meta->word, $newWord, $spannedWord)
                        // }

                        $spannedWord = '<mark class="' . $markType . 'Type" style="background-color: ' . $markColor . '; border-radius: 5px;">' . $spannedWord . '</mark>';
                    }
                    $finalArticle .= $spannedWord;
                }
            }
        }
        $rules = Rule::all();

        return view('article', compact('finalArticle', 'article', 'rules', 'plainArticle', 'articleEN'));
    }


    // change the status of suggestions in metadata
    // 0: no suggestions, 1: accepted suggestion, 2: unique suggestion, 3: disagreement (to do?)
    public function updateMetaStatus($metadata) {

        // $initialStatus = $metadata->status;
        $initialStatus = $metadata->status2;

        // want to check all suggestions for our number of users
        $suggestions = Suggestion::where('userId', '!=', NULL)->get();
        $nrUsers = $suggestions->unique('userId')->count();

        // find suggestions for that specific word
        $sameSuggestions = Suggestion::where('sentence', $metadata->sentenceNr)
                                        ->where('articleID', $metadata->articleId)
                                        ->where('wordPosition', $metadata->wordNr)
                                        ->get(['userId', 'originalText', 'suggestionText', 'sentence', 'wordPosition', 'articleID']);   

        // exclude duplicate suggestions
        $sameSuggestions = collect($sameSuggestions)->unique();

        // check: no similar suggestions -> word should not be highlighted
        if (!$sameSuggestions) {
            $metadata->status = 0;
            $metadata->status2 = 'no_suggestions';
        }

        // multiple users suggested
        if ($sameSuggestions->unique('userId')->count() > 1) {
            $uniqueSuggestions = $sameSuggestions->unique('suggestionText')->count();

            // they all suggested same word -> accepted
            // note: this is a bit unnecessary, could be merged with the else statement
            if ($uniqueSuggestions == 1) {
                $metadata->status = 1;
                $metadata->status2 = 'accepted';
                $metadata->save();

                // find original and suggested word
                $originalText = $metadata->word;
                $suggestionText = $sameSuggestions->unique('suggestionText')->first()->suggestionText;

                // for gender swap: update training and testing data of ML model in background task
                if ((str_replace($suggestionText, "", $originalText) == "ة") || (str_replace($originalText, "", $suggestionText) == "ة")) {
                    
                    // add this word to training data
                    $metadata->inModel = 1;
                    $metadata->save();

                    // run ML model again, create advanced recommendations [ONLINE: in same metadata version]
                    dispatch(new PythonAnalysisJob($metadata, $originalText, $suggestionText));
                }
            } 
            // disagreement on word, see if there is a difference of 2
            else {

                // create sorted array of user suggestions with count
                $suggestionCount = $sameSuggestions->countBy('suggestionText');
                $suggestionCount = json_decode(json_encode($suggestionCount), true);
                arsort($suggestionCount);

                // keys of the array are the most words with most votes in ascending order
                $keys = array_keys($suggestionCount);

                // difference of 2 for most selected word -> ACCEPTED
                if ($suggestionCount[$keys[0]] - $suggestionCount[$keys[1]] >= 2) {
                    
                    // current and replace word
                    $originalText = $metadata->word;
                    $suggestionText = $keys[0];

                    // accepted
                    $metadata->status = 1;
                    $metadata->status2 = 'accepted';
                    $metadata->save();

                    // for gender swap: update training and testing data of ML model in background task
                    if ((str_replace($suggestionText, "", $originalText) == "ة") || (str_replace($originalText, "", $suggestionText) == "ة")) {
                        
                        // add this word to training data
                        $metadata->inModel = 1;
                        $metadata->save();

                        // run ML model again, create advanced recommendations [ONLINE: in same metadata version]
                        dispatch(new PythonAnalysisJob($metadata, $originalText, $suggestionText));
                    }
                    
                // otherwise word stays blue
                } else {
                    $metadata->status = 2;
                    $metadata->status2 = 'not_accepted';
                    $metadata->save();
                }
            }
        }
        // at least one user suggestion
        else {
            $metadata->status = 2;
            $metadata->status2 = 'not_accepted';
            $metadata->save();
        }

        // if new word got accepted, additional update for user scores
        // only update on new suggestions and recommendations, not when recalculating metastatus
        // if ($initialStatus != 1 & $metadata->status == 1) {
        if ($initialStatus != 'accepted' & $metadata->status2 == 'accepted') {

            // (COPIED FROM USERSCONTROLLER) all unique suggestions for that word
            $sameSuggestions = Suggestion::where('sentence', $metadata->sentenceNr)
                                            ->where('articleID', $metadata->articleId)
                                            ->where('wordPosition', $metadata->wordNr)
                                            ->get(['userId', 'originalText', 'suggestionText', 'sentence', 'wordPosition', 'articleID']);
            $sameSuggestions = collect($sameSuggestions)->unique();
            
            // find most accepted suggestion word        
            $suggestionCount = $sameSuggestions->countBy('suggestionText');
            $suggestionCount = json_decode(json_encode($suggestionCount), true); // make array
            arsort($suggestionCount); // rank array
            $keys = array_keys($suggestionCount); // keys are the words & values the counts
            $correctWord = $keys[0]; // first one is the best

            // loop over suggestions and update each user separately
            foreach ($sameSuggestions as $key => $sameSuggestion) {

                // get user instance
                $user = User::where('id', $sameSuggestion->userId)->first();
                if (!$user) {
                    break;
                }

                // get points if correct word was suggested
                if ($sameSuggestion->suggestionText == $correctWord) {

                    // first user to make the accepted suggestion gets more points
                    if ($key == 0) {
                        $user->score += 10;    
                    } else {
                        $user->score += 5;
                    }
                    $user->suggestions_accepted += 1;
                    $user->save();
                }
                // lose points if bad word (not the accepted word) was suggested
                else {
                    $user->score -= 5;
                    $user->save();
                }
            }  
        }
    }


    // save new suggestion through original modal and provide user with recommendations
    public function saveSuggestion() {

        $user = \Auth::user();
        $userId = $user->id;

        // request data         
        $suggestionText = $_POST['suggestionText'];
        $originalText = $_POST['originalText'];
        $articleId = $_POST['articleId'];
        $rule = $_POST['rule'];
        $comment = $_POST['comment'];
        $sentence = $_POST['sentence'];
        $wordPosition = $_POST['wordPosition'];

        // remove punctuation from originaltext (TODO: more punctuation?)
        $originalText = str_replace(".", "", $originalText);
        $originalText = str_replace(":", "", $originalText);
        $originalText = str_replace("(", "", $originalText);
        $originalText = str_replace(")", "", $originalText);

        // check if user has saved this suggestion already
        $suggestion = Suggestion::where('userId', $userId)
                                        ->where('suggestionText', $suggestionText)
                                        ->where('sentence', $sentence)
                                        ->where('articleID', $articleId)
                                        ->where('wordPosition', $wordPosition)
                                        ->first();

        // avoid duplicates, create new suggestion if it doesnt exist yet
        if (!$suggestion) {
            $suggestion = new Suggestion();
            $suggestion->suggestionText = trim($suggestionText); // remove excess spaces around string
            $suggestion->originalText = $originalText;
            $suggestion->articleID = $articleId;
            $suggestion->userId = $userId;
            $suggestion->userName = $user->name;
            $suggestion->ruleID = $rule;
            $suggestion->comment = $comment;
            $suggestion->sentence = $sentence;
            $suggestion->wordPosition = $wordPosition;

            // additional importance measures of the suggestion 
            $occurrences = count(Metadata::where('word', $originalText)->get());
            $affectedArticles = count(Metadata::where('word', $originalText)->get()->unique('articleId'));
            $suggestion->textOccurrences = $occurrences;
            $suggestion->articlesAffected = $affectedArticles;
            $suggestion->save();

            // give user points
            $user->score += 3;
            $user->suggestions_made += 1;

            // add to articles affected
            $articlesAffected = collect(ArticleAR::where('article', 'LIKE', '%' . $originalText . '%')->get('id'))->pluck('id')->count(); 
            $user->articles_affected += $articlesAffected;
            $user->save();
        }

        $suggestionId = $suggestion->id;

        // get metadata instance 
        $metadata = Metadata::where('sentenceNr', $sentence)
                                ->where('articleId', $articleId)
                                ->where('wordNr', $wordPosition)
                                ->where('code', 1)
                                ->first();

        // update status + runs background python script if acceped and also updates userscore
        $this->updateMetaStatus($metadata); 

        // get recommendations (advanced/random)
        list($recommendations, $sentenceIds) = $this->getRecommendations($originalText, $suggestionText, $articleId, $userId, $suggestionId);

        // return our recommendations to the user with ajax
        return response()->json(['success'=>1, 'msg'=> "suggestion saved, recommendations retrieved", 'response' => $recommendations, 'ids' => $sentenceIds]);        
    }


    // get recommendations for the suggestion word
    public function getRecommendations($originalText, $suggestionText, $articleId, $userId, $suggestionId) {
        // outputs a total of max 6 sentences:
        // -> prioritizes advanced recommendations for this suggestion to train model faster
        // -> fill remaining recommendations with random sentences where the same word occurs

        // try to fetch 6 advanced recommendations where the current suggestion applies
        // note: advanced recommendations already exist in the db!
        $advancedRecs = Recommendation::where('originalText', $originalText)->where('suggestionText', $suggestionText)->where('advanced', 1)->take(6)->get();

        // only use the ones the user hasn't responded to yet
        $advancedRecs = [];
        $advancedRecsIds = [];
        foreach ($advancedRecs as $recommendation) {

            // check if user has already seen this recommendation
            $listUsersViewed = json_decode($recommendation->userIdViewed, true);
            if (array_key_exists($userId, $listUsersViewed)) {

                // check user's last response to the recommendation
                $lastResponse = end($listUsersViewed[$userId]);
                
                // if the recommendation is not answered yet, present it back to user
                if ($lastResponse["response"] == 3) {
                    $advancedRecs = $recommendation;
                    $advancedRecsIds[] = $recommendation->id;
                }
            }
        }

        // get additional random recommendations if there aren't enough advanced ones
        $nrAdvancedRecs = count($advancedRecsIds);
        if ($nrAdvancedRecs < 6) {
            list($randomRecommendations, $randomSentenceIds) = $this->getRandomRecommendations($nrAdvancedRecs, $originalText, $suggestionText, $articleId, $userId, $suggestionId);
            if ($nrAdvancedRecs > 1) { 
                $recommendations = array_push($advancedRecs, $randomRecommendations);
                $sentenceIds = array_push($advancedRecsIds, $randomSentenceIds);
            } else {
                $recommendations = $randomRecommendations;
                $sentenceIds = $randomSentenceIds;
            }
        }

        // output max 6 recommendations (advanced and/or random) that should be presented to user
        return array($recommendations, $sentenceIds);
    }

    
    // RANDOM RECOMMENDATIONS: 
    // note: randomly collected sentences are any sentences in other articles where the suggestion word occurs
    // advanced recommendations: output of python analysis background job, stored in db
    public function getRandomRecommendations($nrAdvancedRecs, $originalText, $suggestionText, $articleId, $userId, $suggestionId) {
       
        // first obtain affected articles and remove the current article
        $similar = ArticleAR::where("article", 'like', '%' . $originalText . '%')->where("id", "!=", $articleId)->take(10)->get();

        // suggestion instance
        $suggestion = Suggestion::where('id', $suggestionId)->first();
        $sentenceNr = $suggestion->sentence;
            
        // limit the number of extra suggestions to get a total of 6
        $maxSuggestions = 6 - $nrAdvancedRecs;

        // get all sentences in articles where the original word of the suggestion occurs
        $similarSentences = [];
        $sentenceIds = [];
        foreach ($similar as $line) {

            // error check
            if(!$line){
                break;
            }

            // loop over articles to get sentences
            // (takes too long when selecting sentences straight from metadata)
            $text = $line->article;
            $sentences = explode("<", $text);

            // loop over sentences and present sentence to user only if they haven't answered it yet for that word
            foreach ($sentences as $sentence) {
                
                if (count($similarSentences) < $maxSuggestions) {

                    // include sentences where the whole word is present only (avoid partial words)
                    // solution: str contains space+word OR word+space, this also removes single word recommendations
                    if (str_contains($sentence, " " . $originalText) || str_contains($sentence, $originalText . " ")) {

                        // remove tags
                        try {
                            $sentence = explode(">", $sentence)[1]; 
                        
                        // indexing will fail when the string has no characters after the bracket
                        } catch (\Exception $e) {
                            \Log::info("exception for sentence");
                        }

                        // sentence must not yet be in the current recommendations list
                        if (!in_array($sentence, $similarSentences)) {

                            // check if recommendation is already in database:
                            $existingRecommendation = Recommendation::where('sentence', $sentence)->where('originalText', $originalText)->where('suggestionText', $suggestionText)->first();

                            // recommendation exists already in db
                            if (!$existingRecommendation == null) {

                                // check if user hasn't already seen recommendation
                                $listUsersViewed = json_decode($existingRecommendation->userIdViewed, true);   

                                // user has not seen recommendation
                                if (!array_key_exists($userId, $listUsersViewed)) {
                                    
                                    // add user to usersviewed
                                    $listUsersViewed[$userId] = array(array(
                                                        "suggestionId" => $suggestionId, 
                                                        "response" => 3) // code for not answered
                                                    ); 
                                    $existingRecommendation->userIdViewed = json_encode($listUsersViewed);
                                    $existingRecommendation->save();

                                    // add sentence to current list
                                    $similarSentences[] = $sentence;
                                    $sentenceIds[] = $existingRecommendation->id;
                                    $sentenceId += 1;

                                // user has seen recommendation already for another suggestion
                                } else {

                                    // check if user has answered one of them
                                    $flag = 0;
                                    foreach ($listUsersViewed[$userId] as $i => $separateSuggestion) {

                                        // user has answered
                                        $userResponse = $separateSuggestion['response'];
                                        if ($userResponse != 3) {
                                            $flag = 1;
                                        }
                                    }

                                    // if not, show sentence again and add new suggestion to user info for this recommendation
                                    if ($flag == 0) {

                                        // remove last element
                                        array_pop($listUsersViewed[$userId]);

                                        // info to be added
                                        $newInfo = array(
                                                        "suggestionId" => $suggestionId, 
                                                        "response" => 3
                                                    );

                                        // push to user info array
                                        array_push($listUsersViewed[$userId], $newInfo);
                                        $existingRecommendation->userIdViewed = json_encode($listUsersViewed);
                                        $existingRecommendation->save();

                                        // add sentence to current list
                                        $similarSentences[] = $sentence;
                                        $sentenceIds[] = $existingRecommendation->id;
                                    }    

                                }
                            
                            // sentence is not yet in db for this specific suggestion
                            } else {
                                // create new recommendation
                                $recommendation = new Recommendation();
                                $recommendation->originalText = $originalText;
                                $recommendation->suggestionText = $suggestionText;
                                $recommendation->sentence = $sentence;

                                // add user to users who have seen this recommendation
                                $initiateUsersViewed = array(
                                                        $userId => array( // array because multiple suggestionIds and responses can be stored
                                                                        array(
                                                                            "suggestionId" => $suggestionId, 
                                                                            "response" => 3 // code for not answered
                                                                        )
                                                                    )
                                                        );
                                $recommendation->userIdViewed = json_encode($initiateUsersViewed);
                                $recommendation->save();

                                // add sentence to current list
                                $similarSentences[] = $sentence;
                                $sentenceIds[] = $recommendation->id;
                            }
                        }
                    } 

                // enough recommendation sentences to show user -> save computer power, stop looping 
                } else {
                    break;
                }       
            }    
        }
     
        // mark word for user in sentence
        foreach ($similarSentences as $i => $sentence) {
            $similarSentences[$i] = str_replace($originalText, "<mark>" . $originalText . "</mark>", $sentence);
        }

        return array($similarSentences, $sentenceIds);
    }


    // save the response to the recommendations
    public function saveRecommendation() {

        // user data
        $user = \Auth::user();
        $userId = $user->id; 

        // ajax data
        $recommendationId = $_POST['recommendationId'];
        $recommendation = Recommendation::where("id", $recommendationId)->first();
        $sentence = $recommendation->sentence;
        $valid = $_POST['valid'];
        $originalText = $_POST['originalText'];
        $suggestionText = $_POST['suggestionText'];
        $sentenceNr = (int) $_POST['sentenceNr'];
        $wordPosition = (int) $_POST['wordPosition'];
        $articleId = (int) $_POST['articleId'];
        \Log::info($originalText);
        \Log::info($suggestionText);

        // find related suggestion
        // note: no need to duplicate suggestion info (user, original text, suggested text) just store related id
        $suggestion = Suggestion::where('originalText', $originalText)
                                ->where('suggestionText', $suggestionText)
                                ->where('userId', $userId)
                                ->where('articleID', $articleId)
                                ->where('wordPosition', $wordPosition)
                                ->where('sentence', $sentenceNr)
                                ->first();
        $suggestionId = $suggestion->id;

        $listUsersViewed = json_decode($recommendation->userIdViewed, true);

        // find user in json
        foreach ($listUsersViewed[$userId] as $i => $separateSuggestion) {
            if ($separateSuggestion['suggestionId'] == $suggestionId) {
                $userResponse = $separateSuggestion['response'];
                $index = $i;
            }
        }

        // for dynamic accessing of fields
        $recResponses = array("nrNo", "nrYes", "nrNotSure");
        $recKey = $recResponses[(int) $valid];
        
        $userResponses = array("rec_no", "rec_yes", "rec_not_sure");
        $userKey = $userResponses[(int) $valid];
        

        // give points only once when radio is clicked for the first time (not when changing opinion)
        if ($userResponse == 3) {
            $user->score += 1;

        // response was clicked before but is changed, remove previous response
        } else {
            $recKeyPrev = $recResponses[(int) $userResponse];
            $recommendation[$recKeyPrev] -= 1;
            $userKeyPrev = $userResponses[(int) $userResponse];
            $user[$userKeyPrev] -= 1;
        }

        // update response
        $listUsersViewed[$userId][$index]["response"] = (int) $valid;
        $recommendation[$recKey] += 1;
        $user[$userKey] += 1;
        $user->save();

        // update recommendation status
        $this->updateRecommendationStatus($recommendation);

        // store update usersviewed as json
        $recommendation->userIdViewed = json_encode($listUsersViewed);
        $recommendation->save();

        return response()->json(['success'=>1, 'msg'=> "suggestions saved", 'response' => $sentence]);
    }


    // determine which recommendations are accepted depending on the majority of user votes
    // status can switch between 1 (accepted) and 0 (not accepted, more votes required)
    public function updateRecommendationStatus($recommendation){
        
        // get votes in array
        $instanceArray = $recommendation->attributesToArray();
        $votesCount = [$instanceArray["nrYes"], $instanceArray["nrNo"], $instanceArray["nrNotSure"]];

        // sort array
        arsort($votesCount);

        // determine difference between first and second item
        $difference = $votesCount[0] - $votesCount[1];

        // if it is larger than X, convert to accepted
        // TODO: change this value for more than 3 users
        if ($difference > 2) {
            $recommendation->accepted = 1;
        } else {
            $recommendation->accepted = 0;
        }

        // if accepted:
        //  -> everywhere where this sentence occurs
        //  -> word can be replaced

    }


    // give user penalty and update nr of unsubmitted recommendations
    public function penaltyRecommendation() {

        $user = \Auth::User(); 
        $user->rec_not_submitted += 1;

        if ($user->score > 0) {
            $user->score -= 1;
        }
        $user->save();

        return response()->json(['success'=>1]);
    }


    // change status of words for highlighting (only run this first time, then update with each suggestion)
    // public function suggestionAgreement() {
 
    //     // reset all to 0 first -> no suggestions, no highlight
    //     Metadata::where('articleId', "<=", 50)->where('status', "!=", 0)->update(['status' => 0]);
    //     Metadata::where('articleId', "<=", 50)->where('status2', "!=", 'no_suggestions')->update(['status2' => 'no_suggestions']);

    //     $suggestions = Suggestion::all();

    //     foreach ($suggestions as $suggestion) {

    //         $metadata = Metadata::where('articleId',"<=" , 50)
    //                             ->where('sentenceNr', $suggestion->sentence)
    //                             ->where('articleId', $suggestion->articleID)
    //                             ->where('wordNr', $suggestion->wordPosition)
    //                             ->where('code', 1)
    //                             ->first();

    //         // there is a suggestion for this metadata, so status is at least 2 (unique suggestion)
    //         // TODO: update this value for more than 3 users
    //         $metadata->status = 2;
    //         $metadata->status2 = 'not_accepted';
    //         $metadata->save();

    //         // update the status of the word and do not update userscore
    //         $this->updateMetaStatus($metadata); 
    //     }
    // }


    // find all the previously suggested words and use them as dropdown options
    public function populateDropdown(Request $request) {

        // check status of that word
        $metadata = Metadata::where('sentenceNr', $request->sentence)
                                ->where('articleId', $request->article_id)
                                ->where('wordNr', $request->word_position)
                                ->where('code', 1)
                                ->first();                 
        
        // computer generated suggestions
        // $status = $metadata->status;
        // if ($status == 3) {
        //     $sameSuggestions = $this->computer_T1($metadata->word);
        // }    
        // otherwise use existing suggestions for word on that exact position
        // else {
        $sameSuggestions = Suggestion::where('sentence', $request->sentence)->where('articleID', $request->article_id)->where('wordPosition', $request->word_position)->get();
        // }
        
        // add original word
        $data = [];
        try {
            $data[] = $sameSuggestions[0]->originalText . " (original)";

            // add all unique words to array
            foreach ($sameSuggestions as $suggestion) {
                $word = $suggestion->suggestionText;
                if (!in_array($word, $data)) {
                    $data[] = $word;
                } 
            }
        } catch (\Exception $e) {
            \Log::info($e);
        }
        
        return response()->json(['success'=>1, 'msg'=> "suggestions saved", 'response' => $data]);
    }


    // determine the computer suggestions with rules
    public function computer_T1($word) {

        // simple rule: get all the suggestions for that word (not only for that exact location)
        $suggestedWords = Suggestion::where('originalText', $word)->get()->unique('suggestionText');

        return $suggestedWords;
    }


    // get all the results from the second modal (highlighted words)
    public function saveDropdownResults(Request $request) {

        // get all results in one list
        $selectedWords = $request->selectedWords;
        if ($request->customWord) {
            $selectedWords[] = trim($request->customWord);
        }

        // which word was clicked on by the user
        $user = \Auth::user();
        $userId = $user->id; 
        $articleId = $request->articleId;
        $metadata = Metadata::where('sentenceNr', $request->sentenceNr)
                                ->where('articleId', $articleId)
                                ->where('wordNr', $request->wordPosition)
                                ->where('code', 1)
                                ->first();
        
        // clicked word is the word in the text that was clicked
        $clickedWord = $metadata->word;

        // loop over all suggested words
        foreach ($selectedWords as $selectedWord) {

            // remove the extra original tag
            $selectedWord =  str_replace(" (original)", "", $selectedWord);

            // note: if ($selectedWord !== $clickedWord) we just save it anyways, user can propose original as correct word

            // check if user already submitted this answer
            $suggestion = Suggestion::where('userId', $userId)
                                        ->where('suggestionText', $selectedWord)
                                        ->where('sentence', $request->sentenceNr)
                                        ->where('articleID', $articleId)
                                        ->where('wordPosition', $request->wordPosition)
                                        ->first();

            // there is no similar suggestion yet for this user
            if (!$suggestion) {

                // suggestion is in dropdown, original was clicked to correct
                $suggestionText = $selectedWord;
                $originalText = $clickedWord;

                // create new suggestion
                $suggestion = new Suggestion();
                $suggestion->suggestionText = $suggestionText;
                $suggestion->originalText = $originalText;
                $suggestion->articleId = $articleId;
                $suggestion->userId = $userId;    
                $suggestion->sentence = $request->sentenceNr;
                $suggestion->wordPosition = $request->wordPosition;

                // textoccurrences
                $occurrences = count(Metadata::where('word', $originalText)->get());
                $affectedArticles = count(Metadata::where('word', $originalText)->get()->unique('articleId'));
                $suggestion->textOccurrences = $occurrences;
                $suggestion->articlesAffected = $affectedArticles;

                $suggestion->save();
                
                // give user points
                $user->suggestions_made += 1;
                $user->score += 2;
                $user->save();

                // recommendations
                // TODO: handle recommendations when more than 1 word is selected in dropdown
                // if (count($selectedWords) == 1) {
                // get recommendations (advanced/random)
                
            }
        }

        // update status for that word based on new results
        $this->updateMetaStatus($metadata);

        // return our recommendations to the user with ajax
        list($recommendations, $sentenceIds) = $this->getRecommendations($originalText, $suggestionText, $articleId, $userId, $suggestion->id);
        return response()->json(['success'=>1, 'msg'=> "suggestion saved, recommendations retrieved", 'originalText' => $originalText, 'suggestionText' => $suggestionText, 'response' => $recommendations, 'ids' => $sentenceIds]);
    }


    // indicate for which words we need computer generated suggestions (red highlighted words)
    public function computerSuggestions() {

        Metadata::where('status',"=", 3)->update(['status' => 0]);
        // Metadata::where('status2',"=", 'computer_suggestion')->update(['status2' => 'no_suggestions']);

        // (1) blind replace
        // find all original words in suggestions data
        $suggestions = Suggestion::all();

        // $words = $suggestions->unique('originalText'); // too many
        // words identified in comp_gen_v1 notebook: acceptation rate > 50%
        $words = array_map('str_getcsv', file('/var/www/html/aisiha/data_analysis/dataframes/computer_generated_words/computer_v1_t1.csv'));

        $prepositions = ['مِــن', 'إلى', 'عـَـن', 'على', 'في', 'بِ', 'الباء', 'ك', 'الكاف', 'اللام', 'حتى', 'و'];
        
        // find each word in text that is unmarked and provide the same suggestions
        foreach($words as $word) {

            // positions of identical words
            $identicalWords = Metadata::where('word', $word)
                                    ->where('code', 1) // exclude tags
                                    ->where('status', 0) // words that are not highlighted yet (no suggestions)
                                    ->get();

            if ($identicalWords) {
                foreach($identicalWords as $identicalWord) {
                    $identicalWord->status = 3;
                    $identicalWord->save();
                }
            }            
        }
    }


    // run the gender analysis script for new suggestion
    // * run model each time word is accepted -> update of training/testing data, include the input word in model
    // ** this results in different recommendations
    // note: automatically does not include recommendations that are already in model, these are not in test data!

    // [OFFLINE: when changing metadata version]
    // note: this requires last version of metadata and suggestions, not the one in models
    public function pythonAnalysis() {

        // for all *accepted* words that are not in the model yet
        $acceptedWords = DB::table('metadata_v1')
                                    // ->where('status', 1)
                                    ->where('status2', 'accepted')
                                    ->where('inModel', 0)
                                    ->get();

        // loop over them to get their original replaced word
        foreach ($acceptedWords as $metadataV1) {

            // get accepted word from previous database suggestions
            $sameSuggestions = DB::table('suggestions_v1')
                                    ->select('userId','originalText','suggestionText', 'wordPosition', 'sentence', 'articleID')
                                    ->where('sentence', $metadataV1->sentenceNr)
                                    ->where('articleID', $metadataV1->articleId)
                                    ->where('wordPosition', $metadataV1->wordNr)
                                    ->get();

            // create sorted array of user suggestions with count
            $suggestionCount = $sameSuggestions->countBy('suggestionText');
            $suggestionCount = json_decode(json_encode($suggestionCount), true);

            // ranked array where keys are the most suggested words in ascending order, values are the count
            arsort($suggestionCount);
            $keys = array_keys($suggestionCount);

            // accepted word and original word, remove punctuation so formats match
            $suggestionText = str_replace(".", "", $keys[0]);
            $originalText = str_replace(".", "", $metadataV1->word);

                // ACCEPTED WORD should be different in V1 & V2!
                // $metadataV2 = Metadata::where('sentenceNr', $metadataV1->sentenceNr)
                //                         ->where('articleId', $metadataV1->articleId)
                //                         ->where('wordNr', $metadataV1->wordNr)
                //                         ->where('code', 1)
                //                         ->first();

                // $suggestionText = str_replace(".", "", $metadataV2->word);
                // $originalText = str_replace(".", "", $metadataV1->word);

            // then check if there was a gender switch
            if ((str_replace($suggestionText, "", $originalText) == "ة") || (str_replace($originalText, "", $suggestionText) == "ة")) {
                $metadataV2 = Metadata::where('sentenceNr', $metadataV1->sentenceNr)
                                    ->where('articleId', $metadataV1->articleId)
                                    ->where('wordNr', $metadataV1->wordNr)
                                    ->first();

                // for accepted gender swap: update training and testing data of ML model in background task
                $metadataV2->inModel = 1;
                $metadataV2->save();
                dispatch(new PythonAnalysisJob($metadataV2, $originalText, $suggestionText));

                \Log::info($suggestionText . " / ". $originalText);
            }
        }
    }
}
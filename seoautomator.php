<?php
    require_once 'vendor/autoload.php';
    include 'minheap.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    session_set_cookie_params([
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
    $client = new Google\Client();
    $client->setAuthConfig('client_secret.json');
    $client->addScope(Google\Service\SearchConsole::WEBMASTERS_READONLY);
    if(isset($_SESSION['authusertoken_cipher'])){
        $rawPassphrase = $_ENV['APP_ENCRYPTION_KEY'];
        $encryptionKey = hash('sha256', $rawPassphrase, true);
        $ciphertext = $_SESSION['authusertoken_cipher'];
        $iv = hex2bin($_SESSION['authusertoken_iv']);
        $tag = hex2bin($_SESSION['authusertoken_tag']);
        $decryptedJson = openssl_decrypt($ciphertext, 'aes-256-gcm', $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);
        if ($decryptedJson === false) {
            session_destroy();
            http_response_code(401);
            exit();
        }
        $rawToken = json_decode($decryptedJson, true);
        $client->setAccessToken($rawToken);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit();
    }
    $service = new Google\Service\SearchConsole($client);
    if($_SERVER['REQUEST_METHOD']==='GET'){
        try{
            $oauth2 = new Google\Service\Oauth2($client);
            $userinfo = $oauth2->userinfo->get();
            $sites = $service->sites->listSites();
            $siteEntries = $sites->getSiteEntry();
            if (empty($siteEntries)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'properties' => [], // Sends empty array to trigger your JS
                    'user' => [
                        'email' => $userinfo->email,
                        'profilepic' => $userinfo->picture
                    ]
                ]);
                exit(); // Kills the script right here
            }
            $domainList = [];
            foreach($siteEntries as $site){
                $domainList[] = $site->getSiteUrl();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo(json_encode([
                'properties' => $domainList,
                'user' =>  [
                    'email' => $userinfo->email,
                    'profilepic' => $userinfo->picture
                ]
            ]));
            exit();
        } catch(Exception $e){
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            // for outputting the REAL error directly to the browser i.e debug help
            echo json_encode(['error'=> $e->getMessage()]);
            exit();
        }
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['selected_domain'])){
        $selectedProperty = $_POST['selected_domain'];
        $startdate = date('Y-m-d', strtotime($_POST['startdate']));
        $enddate = date('Y-m-d', strtotime($_POST['enddate']));
        $sites = $service->sites->listSites();
        $validProperties = [];
        foreach($sites->getSiteEntry() as $site){
            $validProperties[] = $site->getSiteUrl();
        }
        if (!in_array($selectedProperty, $validProperties, true)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized property access']);
            exit();
        }
        // ---- end -----

        $request = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startdate);
        $request->setEndDate($enddate);
        $request->setDimensions(['query', 'page']);    
        $request->setRowLimit(25000);
        $sortBy = $_POST['sortby'] ?? '';
        $allowedSorts = [ 'clicks', 'impressions', 'ctr', 'position' ];
        header('Content-Type: application/json');
        if (!in_array($sortBy, $allowedSorts, true)) {
            http_response_code(400);
            echo json_encode([
                "error" => "Invalid sort option"
            ]);
            exit();
        }
        try {
            $response = $service->searchanalytics->query($selectedProperty, $request);
            $updatedToken = $client->getAccessToken();
            if($updatedToken['access_token']!==$rawToken['access_token']){  
                $iv = random_bytes(12);
                $newTag = ""; 
                $encryptedToken = openssl_encrypt(json_encode($updatedToken), 'aes-256-gcm', $encryptionKey, OPENSSL_RAW_DATA, $iv, $newTag);
                $_SESSION['authusertoken_cipher'] = $encryptedToken;
                $_SESSION['authusertoken_iv'] = bin2hex($iv);
                $_SESSION['authusertoken_tag'] = bin2hex($newTag); 
            }
            $rows = $response->getRows();
            $topK = new SplMinPriorityQueue();
            if(!empty($rows)){
                foreach ($rows as $row){
                    $keyword = $row->getKeys()[0];
                    $clicks = (int)  $row->getClicks();
                    $pageUrl = $row->getKeys()[1];
                    $impressions = (int) $row->getImpressions(); 
                    $ctr = (float) $row->getCtr(); 
                    $position = (float) $row->getPosition(); 
                    $item = [
                    "keyword" => $keyword,
                    "page" => $pageUrl,
                    "clicks" => $clicks,
                    "impressions" => $impressions,
                    "ctr" => $ctr,
                    "position" => $position
                    ];
                    $priority = $item[$sortBy]; // sets the priority by which the objects are sorted to be stored in the heap
                    if ($sortBy === 'position') {
                        $priority = -$priority;
                    }
                    if($topK->count()<50){
                        $topK->insert($item, $priority);
                        continue;
                    } else {
                        $topK->setExtractFlags(SplPriorityQueue::EXTR_PRIORITY);
                        $worstPriorityinHeap = $topK->top();
                        $topK->setExtractFlags(SplPriorityQueue::EXTR_DATA);
                        if($priority>$worstPriorityinHeap){
                            $topK->extract();
                            $topK->insert($item, $priority);
                        }
                    }
                }
            }
            $seodata = [];
            $topK->setExtractFlags(SplPriorityQueue::EXTR_DATA);
            while (!$topK->isEmpty()) {
                $seodata[] = $topK->extract(); 
            }
            $seodata = array_reverse($seodata);
            $sendoverdata = json_encode(["data" => $seodata]);
            header('Content-Type: application/json');
            echo($sendoverdata);
            exit();
        } catch(Exception $e){
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
            exit();
        }
    }      
?>
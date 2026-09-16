<?php
    require_once 'vendor/autoload.php';
    session_start();
    $client = new Google\Client();
    $client->setAuthConfig('client_secret.json');
    $client->addScope(Google\Service\SearchConsole::WEBMASTERS_READONLY);
    if(isset($_SESSION['authusertoken'])){
        $client->setAccessToken($_SESSION['authusertoken']);
    } else {
        header("location: login.html");
        exit();
    }
    $service = new Google\Service\SearchConsole($client);
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['selected_domain'])){
        $selectedProperty = $_POST['selected_domain'];
        $startdate = $_POST['startdate'];
        $enddate = $_POST['enddate'];
        $request = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startdate);
        $request->setEndDate($enddate);
        $request->setDimensions(['query']);    
        $request->setRowLimit(50);
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
            $rows = $response->getRows();
            $seodata = [];
            if(!empty($rows)){
                foreach ($rows as $row){
                    $keyword = $row->getKeys()[0];
                    $clicks = $row->getClicks();
                    $impressions = $row->getImpressions(); 
                    $ctr = $row->getCtr(); 
                    $position = $row->getPosition(); 
                    $seodata[] = [
                    "keyword" => $keyword,
                    "clicks" => $clicks,
                    "impressions" => $impressions,
                    "ctr" => $ctr,
                    "position" => $position
                    ];
                }
            }
            switch ($sortBy) {
                case "clicks":
                    usort($seodata, function ($a, $b) {
                        return $b["clicks"] <=> $a["clicks"];
                    });
                    break;

                case "impressions":
                    usort($seodata, function ($a, $b) {
                        return $b["impressions"] <=> $a["impressions"];
                    });
                    break;

                case "ctr":
                    usort($seodata, function ($a, $b) {
                        return $b["ctr"] <=> $a["ctr"];
                    });
                    break;

                case "position":
                    usort($seodata, function ($a, $b) {
                        return $a["position"] <=> $b["position"];
                    });
                    break;
            }
            $sendoverdata = json_encode(["data" => $seodata]);
            header('Content-Type: application/json');
            exit();
        } catch(Exception $e){
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
            exit();
        }
    }      
?>
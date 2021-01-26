<?php

// DB Definition
define('DB_CONFIG', 'mysql:host=localhost;dbname=crawler;charset=utf8');
define('DB_USER', 'root');
define('DB_PASSWORD', '');



// This function sends curl request to get some page, calls to insertToDB function & fetchs the links & recall himself
// gets params: $url - url for request, $x - which stops the recursive iteration.
// returns nothing

function getSite($url, $x) {

    if ($x < 4) {

        // sending curl request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $error  = curl_error($ch);
        curl_close($ch);
    
        if ($errno === 0) {
    
            // inserting to DB operation
            insertToDB($url, $result);
    
            // finding the 'href' property in the result (href with "" & '')
            preg_match_all('/href="http.+?"/', $result, $matches1);
            preg_match_all("/href='http.+?'/", $result, $matches2);

            // marge the results
            $matches = array_merge($matches1[0], $matches2[0]);
        
            foreach($matches as $str) {

                // continue if the link is an image or a css/js file
                if ( preg_match("/$(\.png|\.jpg|\.jpeg|\.gif|\.css|\.js)/", $str) ) continue;

                // removing some characters
                $url = str_replace(['href=', '"', "'"],'', $str);

                // recalling this function
                getSite($url, $x + 1);
            }

        }

    }

}


// Inserts the URL and the site content to DB (the url column defined as a Unique index)
// gets url & content params
// returns nothing

function insertToDB($url, $content) {
    try {
        $pdo = new PDO(DB_CONFIG, DB_USER, DB_PASSWORD);
    
        $sql = "INSERT IGNORE INTO sites VALUES('',:site_url,:content)";
        $query = $pdo->prepare($sql);
        $res = $query->execute(['site_url' => $url, 'content' => $content]);
        return $res;
    }
    catch (PDOException $e) {
        // echo "Connection failed: " . $e->getMessage();
    }
}


// Gets all the links from DB
// without params
// returns the results

function getLinksFromDB() {
    try {
        $pdo = new PDO(DB_CONFIG, DB_USER, DB_PASSWORD);

        $res =  $pdo->query('SELECT url FROM sites')->fetchAll(PDO::FETCH_ASSOC);

        return $res;
    }
    catch (PDOException $e) {
        // echo "Connection failed: " . $e->getMessage();
    }
}


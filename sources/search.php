<?php
use Abraham\TwitterOAuth\TwitterOAuth;

function PageMain() {
	global $TMPL, $conf, $db, $tweets;
	
	$all = 'Sorry, it seems that the movie you where looking for doesn\'t exist or we don\'t have it in our database...';

	$text = $_GET['a'];
	$keyword = htmlspecialchars(urldecode($_GET['q']), ENT_QUOTES);
	// $twitterQuery = rawurlencode(trim($_GET['q']));
	$filter = htmlspecialchars($_GET['f'], ENT_QUOTES);
	$order = htmlspecialchars($_GET['o'], ENT_QUOTES);
	
	// Incepe filtrul
	$qo1 = "\""; // Adauga +-ul pentru toate conditiile, exceptand cautarea exacta,
	$qo2 = "\"";  // si-ar putea fii eliminat, daca n-ar exista cautarea exacta (filtrul 4).
	if ($filter == 1) {
		$fil = "+"; // Potriveste cuvant1 + cuvant2.
		} elseif ($filter == 2) {
		$fil = "-"; // Potriveste cuvant1 dar nu si cuvant2.
		} elseif ($filter == 3) {
		$fil = "~"; // Potriveste cuvant1, dar marcheaza rezultatele ce contin cuvant2 mai putin relevante.
		} elseif ($filter == 4) {
		$qo1 = "'\""; // Folosit la cautarea exacta, cuvant-ul cautat trebuie
		$qo2 = "\"'"; // inclus neaparat intre " ", ex: "cuvant1 cuvant2";
		} else {
		$fil = ""; // Optiunea default, cand nu este nici un filtru selectat.
	}
	// Ascedent sau Descendent
	// if ($order == 1) {
	// $ord = "ASC";
	// $TMPL['filtru'] = 'in <strong>ascending</strong> order.';
	// } elseif ($order == 0) {
	// $ord = "DESC";
	// $TMPL['filtru'] = 'from <strong>Twitter</strong> (default).';
	// } else { 
	// $ord = "DESC";
	// }
	
	// Aranjeaza query-ul in functie de numarul cuvintelor.
	$arr = explode(' ', $keyword); 
	$wrdCount = str_word_count($keyword);
	if($wrdCount == 1) {
		$out = '+'.$arr[0].'*';
		} elseif ($wrdCount == 2) {
		$out = '+'.$arr[0].'* '.$fil.''.$arr[1].'';
		} elseif ($wrdCount >= 3) {
		$out = '+'.$arr[0].'* '.$fil.''.$arr[1].' '.implode(' ', array_slice($arr, 2)).'';
	}
	$keywordUnclean = trim(preg_replace("/&#?[a-z0-9]+;/i",'',(strip_tags($keyword))));
	$keywordClean = substr(preg_replace('/\s+/', ' ', $keywordUnclean), 0, 255);
		
	if(!empty($keywordClean) && strlen($keywordClean) >= 3) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		$keywordUnclean = trim(preg_replace("/&#?[a-z0-9]+;/i",'',(strip_tags($keyword))));
		$keywordClean = substr(preg_replace('/\s+/', ' ', $keywordUnclean), 0, 255);
		
		$selectKeyword = "SELECT * FROM keywords WHERE keyword = '$keywordClean'";
		if(mysqli_fetch_row(mysqli_query($db, $selectKeyword)) == NULL) {
		$keywordQuery = sprintf("INSERT INTO `keywords` (`keyword`, `count`) VALUES ('%s', '1')", mysqli_real_escape_string($db, $keywordClean));
		mysqli_query($db, $keywordQuery); 
		} else {
		$keywordQuery = sprintf("UPDATE `keywords` SET `count` = `count` + 1 WHERE keyword = '%s'", mysqli_real_escape_string($db, $keywordClean));
		mysqli_query($db, $keywordQuery);
		}
		
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		/// SOCIAL MEDIA SEARCH

		if ($filter=="" || $filter == 1) {
				$TMPL['filtru'] = 'from <strong>Twitter</strong> (default).';
				$cache_file = 'cache/twitter-cache-'.$keyword.'.data'; //saved with .data extension
			if (file_exists($cache_file) && !$_GET['force-refresh']) {
				$starttime = microtime(true);
			    $data = unserialize(file_get_contents($cache_file));
			    if (($data['timestamp'] > (time() - 15 * 60))) {
			        $tweets = json_decode($data['tweets']);
			        $tweets = $tweets->statuses;
			        foreach ($tweets as $tweet) {
						$x[] = array(
								  'title' =>  $tweet->text,
								  'description' => "@".$tweet->user->screen_name ." ". $tweet->text,
					   			  'authors' => $tweet->user->screen_name,
					   			  'id' =>  $tweet->id,
					   			  'profile_url' => 'https://twitter.com/'.$tweet->user->screen_name.'/profile_image?size=mini',
					   			  'url' => 'https://twitter.com/'.$tweet->user->screen_name .'/status/'.$tweet->id,
					   			  'date' =>date('Y-m-d H:i', strtotime($tweet->created_at)));
					}
			    }
		    	$endtime = microtime(true);
			}
			if (!$tweets) {
				$starttime = microtime(true);
				require "API/twitteroauth-v1.1/autoload.php";

				$num_tweets = 100;
				$consumer_key = 'd4oJy8FfxMasf905ATCN6FXA8';
				$consumer_secret = 'XEMqZsnw178ybltGPRCmhQ7XtU0CQ5yOtXlQ42cw6uhkTwWWhM';
				$access_token = '3850073433-WrcDiTiPmuWkN9qjy3zctLzYlrjjA6L5zlHzGvM';
				$access_token_secret = 'bmImIuomdvxTcE0pHHzeYkZRewJpBno2aI5qitAiI4wBZ';

				$connection = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
				$connection->setTimeouts(100, 150);
				$content = $connection->get("account/verify_credentials");
				$tweets = $connection->get("search/tweets", ["q"=> $keyword, "count" => $num_tweets]);


			    
				if (strpos(json_encode($tweets), 'Rate limit exceeded') == true) {
					$time_start = microtime(true);
				    if (file_exists($cache_file)) {
				        $data = unserialize(file_get_contents($cache_file));
				        $tweets = json_decode($data['tweets']);
				        $tweets = $tweets->statuses;
				        foreach ($tweets as $tweet) {
							$x[] = array(
								  'title' =>  $tweet->text,
								  'description' => "@".$tweet->user->screen_name ." ". $tweet->text,
					   			  'authors' => $tweet->user->screen_name,
					   			  'id' =>  $tweet->id,
					   			  'profile_url' => 'https://twitter.com/'.$tweet->user->screen_name.'/profile_image?size=mini',
					   			  'url' => 'https://twitter.com/'.$tweet->user->screen_name .'/status/'.$tweet->id,
					   			  'date' =>date('Y-m-d H:i', strtotime($tweet->created_at)));
						}
				        $time_end = microtime(true);
				    }
			    } else {
			        $data = array('tweets' => json_encode($tweets), 'timestamp' => time());
			        file_put_contents($cache_file, serialize($data));
			        $tweets = $tweets->statuses;
			        foreach ($tweets as $tweet) {
						$x[] = array(
								  'title' =>  $tweet->text,
								  'description' => "@".$tweet->user->screen_name ." ". $tweet->text,
					   			  'authors' => $tweet->user->screen_name,
					   			  'id' =>  $tweet->id,
					   			  'profile_url' => 'https://twitter.com/'.$tweet->user->screen_name.'/profile_image?size=mini',
					   			  'url' => 'https://twitter.com/'.$tweet->user->screen_name .'/status/'.$tweet->id,
					   			  'date' =>date('Y-m-d H:i', strtotime($tweet->created_at)));
					}
			    }
			    $endtime = microtime(true);
			}

			$duration = $endtime - $starttime;
			$TMPL['duration'] = substr($duration, 0, 6);
			
			$TMPL_old = $TMPL; $TMPL = array();
			$skin = new skin('search/rows'); $all = '';

			foreach ($x as $TMPL) {
				// Title
				$TMPL['site_url'] = $conf['url'];
				
				$TMPL['title'] = highlightWords(substr($TMPL['title'], 0, 64), $keyword);
				if(strlen($TMPL['title']) >= 64) { $TMPL['title'] = $TMPL['title'].'...';}
				
				// Description & Body	
				$TMPL['description'] = highlightWords(substr($TMPL['description'], 0, 200), $keyword);
				$TMPL['body'] = highlightWords(substr($TMPL['body'], 0, 200), $keyword);
				if(!empty($TMPL['description'])) {
					if(strlen($TMPL['description']) >= 200) { $TMPL['description'] = $TMPL['description'].'...';}
				} else { 
					if(strlen($TMPL['body']) >= 200) { $TMPL['description'] = $TMPL['body'].'...';} else { $TMPL['description'] = $TMPL['body']; }
				}
				
				// Author
				if(empty($TMPL['author'])) { $TMPL['authors'] = '';} else { $TMPL['authors'] = ' - <img src="'.$conf['url'].'/images/res_aut.png" height="10" width="10" />'.$TMPL['author'].'';}
				
				// Url
				$TMPL['urlCite'] = strtolower(highlightWords($TMPL['url'], $keyword));

				$all .= $skin->make();
			}

			
			$TMPL = $TMPL_old; unset($TMPL_old);
			
			$TMPL['rows'] = $all;

			$text = 'content';
		}
		//YouTube
		if ($filter == 2) {
			$TMPL['filtru'] = 'from <strong>YouTube</strong>';
			if (!file_exists('API/youtube/autoload.php')) {
			  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
			}

			require_once 'API/youtube/autoload.php';
			$DEVELOPER_KEY = 'AIzaSyCvFvTZUCmMPCydxD68P2nKQptpI_VfMzA';

			  $client = new Google_Client();
			  $client->setDeveloperKey($DEVELOPER_KEY);

			  // Define an object that will be used to make all API requests.
			  $youtube = new Google_Service_YouTube($client);
		  // try{
			  	$starttime = microtime(true);
			    $searchResponse = $youtube->search->listSearch('id,snippet', array(
			        'q' => $keyword,
			        'maxResults' => 50,
			      ));
			    $endtime = microtime(true);
			    $duration = $endtime - $starttime;
				$TMPL['duration'] = substr($duration, 0, 6);
				$videos = '';
			    $channels = '';
			    $playlists = '';

			    foreach ($searchResponse['items'] as $searchResult) {
			    	if ($searchResult['id']['kind'] == 'youtube#video') {
						$x[] = array(
								  'title' =>  $searchResult['snippet']['title'],
								  'description' => $searchResult['snippet']['description'],
					   			  'authors' => $searchResult['snippet']['title'],
					   			  'id' =>  $searchResult['id']['videoId'],
					   			  'profile_url' => 'https://img.youtube.com/vi/'.$searchResult['id']['videoId'].'/1.jpg',
					   			  'url' => 'https://www.youtube.com/watch?v='.$searchResult['id']['videoId'],
					   			  'date' =>date('Y-m-d H:i', strtotime($searchResult['snippet']['publishedAt'])));
						}
					}
				$TMPL_old = $TMPL; $TMPL = array();
				$skin = new skin('search/rows'); $all = '';

				foreach ($x as $TMPL) {
					// Title
					$TMPL['site_url'] = $conf['url'];
					
					$TMPL['title'] = highlightWords(substr($TMPL['title'], 0, 64), $keyword);
					if(strlen($TMPL['title']) >= 64) { $TMPL['title'] = $TMPL['title'].'...';}
					
					// Description & Body	
					$TMPL['description'] = highlightWords(substr($TMPL['description'], 0, 200), $keyword);
					$TMPL['body'] = highlightWords(substr($TMPL['body'], 0, 200), $keyword);
					if(!empty($TMPL['description'])) {
						if(strlen($TMPL['description']) >= 200) { $TMPL['description'] = $TMPL['description'].'...';}
					} else { 
						if(strlen($TMPL['body']) >= 200) { $TMPL['description'] = $TMPL['body'].'...';} else { $TMPL['description'] = $TMPL['body']; }
					}
					
					// Author
					if(empty($TMPL['author'])) { $TMPL['authors'] = '';} else { $TMPL['authors'] = ' - <img src="'.$conf['url'].'/images/res_aut.png" height="10" width="10" />'.$TMPL['author'].'';}
					
					// Url
					$TMPL['urlCite'] = strtolower(highlightWords($TMPL['url'], $keyword));

					$all .= $skin->make();
				}

				
				$TMPL = $TMPL_old; unset($TMPL_old);
				
				$TMPL['rows'] = $all;

				$text = 'content';

		 //    } catch (Google_Service_Exception $e) {

			//   } catch (Google_Exception $e) {

			//   }
			// }

		    // Add each result to the appropriate list, and then display the lists of
		    // matching videos, channels, and playlists.


		}
			

	}else { 
	$TMPL_old = $TMPL; $TMPL = array();
		$skin = new skin('search/error');
		$all .= $skin->make();			
		$TMPL = $TMPL_old; unset($TMPL_old);
		
		$TMPL['error'] = '<strong>What are you looking for?</strong> <br /><br />Some tips:
		<ul>
			<li>Be as descriptive as possible.</li>
			<li>Make sure you try different combination of keywords.</li>
			<li>Your query must be longer than two characters.</li>
		</ul>';
		
		$text = 'content';
	}



	if(!empty($_GET['f'])) {
	$TMPL['f'] = '&f='.$_GET['f'].'';
	}
	if(!empty($_GET['o'])) {
	$TMPL['o'] = '&o='.$_GET['o'].'';
	}
	$queryAds = "SELECT ad2,ad3,title from users where id = '1'";
	$resultAds = mysqli_fetch_row(mysqli_query($db, $queryAds));
	
	if(!empty($resultAds[0])) { $TMPL['ad2'] = '<div class="adSpace2">'.$resultAds[0].'</div>'; } else { $TMPL['ad2'] = ''; }
	if(!empty($resultAds[1])) { $TMPL['ad3'] = '<div class="adSpace3">'.$resultAds[1].'</div>'; } else { $TMPL['ad3'] = ''; }
	
	$TMPL['query'] = $keyword;
	$TMPL['title'] = $keyword.' - '.$resultAds[2].'';

	$skin = new skin("search/$text");
	return $skin->make();
}
?>
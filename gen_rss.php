<?php
include("ganon.php");

function file_get_contents_alter($url) {
	$toURL = $url;
	$ch = curl_init();
	$opts = array(
			'http'=>array(
				'protocol_version'=>'1.1',
				'method'=> "GET",
				'header'=> "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0\r\n" .
				"Host: news.mingpao.com\r\n" .
				"DNT: 1\r\n" .
				"Accept-Language: zh-tw,zh;q=0.8,en-us;q=0.5,en;q=0.3\r\n" .
				"Connection: keep-alive",
				"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n"
				)
		     );
	$options = array(
			CURLOPT_URL=>$toURL,
			CURLOPT_HEADER=>0,
			CURLOPT_VERBOSE=>0,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_USERAGENT=>"Mozilla/4.0 (compatible;)",
			CURLOPT_POST=>false,
			CURLOPT_HTTPHEADER=>split("\r\n",$opts['http']['header'])
			);
	curl_setopt_array($ch, $options);
	$result = curl_exec($ch); 
	curl_close($ch);
	$result = iconv('BIG5-HKSCS', 'UTF-8//IGNORE',$result);
	return $result;
}

$mingpao_channels = ['ga'];
//$news_srcs = ['http://news.mingpao.com/20131020/gaindex.htm'];
if (!empty($argv)&&count($argv)>=2) $mingpao_channels = array_slice($argv,1);

$homepage = file_get_contents_alter("http://news.mingpao.com/");
preg_match('|<base href="http://news.mingpao.com/([0-9]*)/">|', $homepage, $homeres);
$today=$homeres[1];

$rss = new SimpleXMLElement('<rss version="2.0"/>');

foreach ($mingpao_channels as $ch) {
	fprintf(fopen("php://stderr","w"), "channel $ch\n");
	
	$src = 'http://news.mingpao.com/'.$today.'/'.$ch.'index.htm';
	$indexpage = file_get_contents_alter($src);

	$src_url = parse_url($src);
	$basepath = sprintf( '%s://%s%s/'  , $src_url['scheme'], $src_url['host'], dirname($src_url['path']));

	preg_match('|<title>(.*)</title>|', $indexpage, $pagetitle);

	$html = str_get_dom($indexpage);
	$indexpage = $html('div.news_box', 0)->html();
	preg_match_all('|<a href="([a-z]{3,3}[0-9]{1,2}.htm)">(.*?)</a>|', $indexpage, $titles, PREG_SET_ORDER);

	//preg_match_all('|<a href="('.$ch.'[a-z]?[0-9]{1,2}.htm)">(.*?)</a>|', $indexpage, $titles, PREG_SET_ORDER);
	array_walk($titles, function(&$a, $key, $basepath){ $a[1] = $basepath . $a[1]; } , $basepath);


	$rss_channel=$rss->addChild('channel');
	$channel = array('title'=>$pagetitle[1],'link'=>$src/*,'description'=>"test"*/,'lastBuildDate'=>date("r"));
	//array_walk(array_flip($channel), array ($rss_channel, 'addChild'));
	//array_walk($channel, function ($v, $k, $dom) { $dom->addChild($k,$v); }, $rss_channel);
	array_walk($channel, function ($v, $k, $dom) { $dom->$k=$v; }, $rss_channel);

	$titles = array_filter($titles, function($a){
			static $arr=array();
			if (array_search($a[1],$arr)===false)
			{
			array_push($arr,$a[1]);
			return true;
			}
			else {
			return false;
			}
			});

	foreach ($titles as $title){
		$articlepage = file_get_contents_alter($title[1]);
		fprintf(fopen("php://stderr","w"), "$title[1] - got\n");
		$html = str_get_dom($articlepage);

		$rss_item=$rss_channel->addChild('item');
		$item = array( 'title' => $html('div#articleheading', 0)->getPlainText(),
						'link' => $title[1],
						'guid' => $title[1],
				//'description' =>  $html('span#advenueINTEXT', 0)->getPlainText()
				//'description' =>  implode("<br/>", array_map(function ($p){return $p->getPlainText(); },$html('span#advenueINTEXT', 0)->select('p')))
				'description' =>  $html('span#advenueINTEXT', 0)->select('div#newscontent01',0)->getPlainText()."<br/><br/>".implode("<br/>", array_map(function ($p){return $p->getPlainText(); },$html('span#advenueINTEXT', 0)->select('div#newscontent02',0)->select('p')))
			     );
		//array_walk(array_flip($item), array ($rss_item, 'addChild'));
		//array_walk($item, function ($v, $k, $dom) { $dom->addChild($k,$v); }, $rss_item);
		array_walk($item, function ($v, $k, $dom) { $dom->$k=$v; }, $rss_item);
	}
}

echo $rss->asXML();
?>

<?php
// github tags feed generator by samrayner:
// https://github.com/samrayner/GitHub-Tags-Feed

$username = "ssilence";
$repo_name = "selfoss";

$cacheFile = "rss.cache";
if(file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
    header("Content-Type: text/xml");
    echo file_get_contents($cacheFile);
    return;
}

function status_ok($curl) {
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    return ($status >= 200 && $status < 300);
}

$repo_url = "https://api.github.com/repos/$username/$repo_name";
$list_url = $repo_url."/git/refs/tags/";

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

curl_setopt($curl, CURLOPT_URL, $repo_url);
$response = curl_exec($curl);

if(!status_ok($curl)) {
    header("HTTP/1.1 404 Not Found");
    exit("Repository doesn't exist or is private.");
}

$repo = json_decode($response, true);

curl_setopt($curl, CURLOPT_URL, $list_url);
$response = curl_exec($curl);

if(!status_ok($curl)) {
    header("HTTP/1.1 404 Not Found");
    exit("No tags for this repository yet.");
}

$tag_refs = array_reverse(json_decode($response, true));

$tags = array();
foreach($tag_refs as $tag) {
    //only match version tags
    //if(preg_match('~/v\d+(\.\d+)*$~', $tag["ref"])) {
    curl_setopt($curl, CURLOPT_URL, $tag["object"]["url"]);
    $tags[] = json_decode(curl_exec($curl), true);
    //}
}

curl_close($curl);

function escape(&$var) {
    $var = htmlspecialchars($var, ENT_NOQUOTES | 16); //ENT_XML1 = 16
}

escape($repo["name"]);
escape($repo["description"]);
escape($username);


header("Content-Type: application/xml;");
ob_start();
echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<rss version="2.0">
    <channel>
        <title>Changelog for <?php echo $repo["name"] ?></title>
        <link><?php echo $repo["html_url"] ?></link>
        <description><?php echo $repo["description"] ?></description>
        <language>en</language>
        <copyright>Copyright <?php echo date("Y") ?>, <?php echo $username ?></copyright>
        <docs>http://blogs.law.harvard.edu/tech/rss</docs>
        <pubDate><?php echo date("r", strtotime($repo["pushed_at"])) ?></pubDate>
        <lastBuildDate><?php echo date("r", strtotime($repo["updated_at"])) ?></lastBuildDate>
        
        <?php foreach($tags as $tag): ?>
        <item>
        
            <?php 
                escape($tag["tag"]);
                escape($tag["tagger"]["email"]);
                escape($tag["message"]);
            ?>

            <title><?php echo $tag["tag"] ?></title>
            <link><?php echo "https://github.com/$username/$repo_name/zipball/".$tag["tag"] ?></link>
            <pubDate><?php echo date("r", strtotime($tag["tagger"]["date"])) ?></pubDate>
            <guid><?php echo "https://github.com/$username/$repo_name/commit/".$tag["sha"] ?></guid>
            <author><?php echo $tag["tagger"]["email"] ?></author>
            <description><?php echo $tag["message"] ?></description>

        </item>
        <?php endforeach ?>
        
    </channel>
</rss>
<?PHP
    $content = ob_get_contents();
    ob_end_clean();
    header("Content-Type: text/xml");
    file_put_contents($cacheFile, $content);
    echo $content;
?>
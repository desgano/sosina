<?php
function get_result()
{
    // GET引数からマイリスト番号を取得する
    $id = $_GET['id'] ?? "";
    if ($id == "")
        return "Error: No ID specified.";
    if (!preg_match('/^\d+$/', $id))
        return "Error: Bad ID specified.";

    // ニコニコ動画に接続してマイリストのRSSを読み出す
    $url = "https://www.nicovideo.jp/mylist/{$id}?rss=2.0";
    $opts = stream_context_create(["http" => ["ignore_errors" => true]]);
    $contents = @file_get_contents($url, false, $opts);

    // HTTPステータスが200でなければエラーとみなす
    $stat = $http_response_header[0];
    $code = preg_replace('/^\S+\s+(.*)/', '$1', $stat);
    if (intval($code) != 200)
        return "Error: mylist/{$id}: {$code}";

    // SimpleXML形式に変換する
    libxml_use_internal_errors(false);
    $rss = simplexml_load_string($contents);
    if (count(libxml_get_errors()) > 0)
        return "Error: mylist/{$id} is broken.";

    // マイリストのtitleとcreatorを得る
    $title =  $rss->channel->title;
    $title = preg_replace('/^マイリスト\s+(.*)‐ニコニコ動画$/', '$1', $title);
    $creator = @($rss->channel->xpath("//dc:creator")[0]);
    $result = "<!-- mylist/{$id} 「{$title}」 by {$creator} -->\n";

    // 「動画番号=>"[sm動画番号, 動画タイトル],"」の連想配列を作る
    $arr = [];
    foreach ($rss->channel->item as $item) {
        $vid = preg_replace('|^.*/sm(\d+)\?.*$|', '$1', $item->link);
        $arr[$vid] = "<option value=\"sm{$vid}\">{$item->title}</option>\n";
    }
    // キー(動画番号)順にソートした値を結合する
    ksort($arr, SORT_NUMERIC);
    $result .= implode("", array_values($arr));

    // 結果を返す
    return $result;
}

$result = get_result();
header('Content-Type: text/plain; charset=utf-8');
header('Content-Length: ' . strlen($result));
print($result);
?>

<?php

$apiKey = 'AIzaSyBirL9XMWHNxuVUUGPBdSqu9UxqcY06dVo';

$content = file_get_contents('README.md');
$lines = explode("\n", $content);
$youtubers = [];

foreach ($lines as $line) {
    if (empty($line = trim($line))) {
        continue;
    }

    $handle = substr($line, strpos($line, '[@') + 2, strpos($line, ']') - (strpos($line, '[@') + 2));
    $url = substr($line, strpos($line, '(https://') + 1, strpos($line, ')**') - (strpos($line, '(https://') + 1));
    $descriptionAndName = substr($line, strpos($line, '**:') + 4);

    $splitPos = strpos($descriptionAndName, ' ‧ ');

    if ($splitPos !== false) {
        $name = substr($descriptionAndName, 0, $splitPos);
        $description = substr($descriptionAndName, $splitPos + 5);
    } else {
        $name = null;
        $description = $descriptionAndName;
    }

    $youtubers[] = compact('handle', 'url', 'name', 'description');
}

$total = count($youtubers);
$progress = 0;

foreach ($youtubers as $index => $youtuber) {
    preg_match('/channel_id=([a-zA-Z0-9_-]+)/', file_get_contents($youtuber['url']), $matches);
    $channelId = $matches[1] ?? null;

    $json_url = "https://www.googleapis.com/youtube/v3/channels?part=statistics&id={$channelId}&key={$apiKey}";
    $data = json_decode(file_get_contents($json_url), true);
    $followers = $data['items'][0]['statistics']['subscriberCount'];

    $youtubers[$index]['channelId'] = $channelId;
    $youtubers[$index]['followers'] = $followers;

    $progress++;
    echo "\r[";
    $barSize = (int) round($progress / $total * 50);
    echo str_repeat('⏳', $barSize);
    echo str_repeat(' ', 50 - $barSize);
    echo ']';
}

uasort($youtubers, function ($a, $b) {
    return $b['followers'] <=> $a['followers'];
});

function followersCount($count) {
    if ($count > 1000000) {
        return round($count / 1000000, 1) . 'M';
    }

    if ($count > 1000) {
        return round($count / 1000, 1) . 'K';
    }

    return $count;
}

$sortedList = '';
foreach ($youtubers as $youtuber) {
    if ($youtuber['name'] !== null) {
        $description = followersCount($youtuber['followers']) . " ‧ {$youtuber['name']} ‧ {$youtuber['description']}";
    } else {
        $description = followersCount($youtuber['followers']) . " ‧ ". $youtuber['description'];
    }

    $sortedList .= "- **[@{$youtuber['handle']}](https://www.youtube.com/@{$youtuber['handle']})**: {$description}\n";
}

file_put_contents('README.md', $sortedList);

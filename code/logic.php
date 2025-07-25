<?php
function validateInput($username, $startDate, $endDate) {
    if (!preg_match('/^[a-zA-Z0-9_ ]{1,50}$/', $username)) {
        return "Invalid username. Use alphanumeric characters, spaces, or underscores.";
    }
    if ($startDate && !DateTime::createFromFormat('Y-m-d', $startDate)) {
        return "Invalid start date format.";
    }
    if ($endDate && !DateTime::createFromFormat('Y-m-d', $endDate)) {
        return "Invalid end date format.";
    }
    if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
        return "Start date cannot be after end date.";
    }
    return true;
}

function fetchUserData($username, $startDate, $endDate, $redirectFilter, $namespaceFilter) {
    $cacheKey = md5($username . $startDate . $endDate . $redirectFilter . $namespaceFilter);
    $cacheFile = "cache/$cacheKey.json";
    $cacheTime = 3600; // 1 hour cache

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $pages = json_decode(file_get_contents($cacheFile), true);
    } else {
        $namespace = $namespaceFilter === 'all' ? '' : '0';
        $pages = [];
        $uccontinue = '';
        do {
            $apiUrl = "https://justapedia.org/api.php?action=query&list=usercontribs&ucuser=" . urlencode($username) .
                "&ucnamespace=$namespace&ucprop=title|timestamp|size&ucshow=new&uclimit=max&format=json&origin=*" .
                ($uccontinue ? "&uccontinue=" . urlencode($uccontinue) : '');

            $response = @file_get_contents($apiUrl);
            if ($response === false) {
                echo "<p class='error-message'>Error fetching data from API.</p>";
                return;
            }

            $data = json_decode($response, true);
            if (!isset($data['query']['usercontribs'])) {
                echo "<p class='error-message'>No contributions found.</p>";
                return;
            }

            $pages = array_merge($pages, $data['query']['usercontribs']);
            $uccontinue = $data['continue']['uccontinue'] ?? '';
        } while (!empty($uccontinue));

        if (!is_dir('cache')) mkdir('cache', 0755, true);
        file_put_contents($cacheFile, json_encode($pages));
    }

    if ($startDate || $endDate) {
        $pages = array_values(filterByDateRange($pages, $startDate, $endDate));
    }
    $validPages = array_values(filterRedirects($pages, $redirectFilter));
    
    // Pass total article count to displayResults
    displayResults($username, $validPages, $startDate, $endDate, $redirectFilter, $namespaceFilter, count($validPages));
}

function filterByDateRange($pages, $startDate, $endDate) {
    return array_filter($pages, function ($page) use ($startDate, $endDate) {
        $pageDate = strtotime($page['timestamp']);
        return (!$startDate || $pageDate >= strtotime($startDate)) &&
               (!$endDate || $pageDate <= strtotime($endDate));
    });
}

function filterRedirects($pages, $redirectFilter) {
    if ($redirectFilter === 'include') return $pages;

    $batchSize = 50;
    $titles = array_column($pages, 'title');
    $redirectStatus = [];

    foreach (array_chunk($titles, $batchSize) as $titleBatch) {
        $titlesQuery = implode('|', array_map('urlencode', $titleBatch));
        $apiUrl = "https://justapedia.org/api.php?action=query&titles=$titlesQuery&prop=info&inprop=isredirect&format=json&origin=*";

        $response = @file_get_contents($apiUrl);
        if ($response === false) continue;

        $data = json_decode($response, true);
        if (isset($data['query']['pages'])) {
            foreach ($data['query']['pages'] as $page) {
                $isRedirect = isset($page['redirect']);
                $redirectStatus[$page['title']] = $isRedirect;
            }
        }
    }

    return array_filter($pages, function ($page) use ($redirectFilter, $redirectStatus) {
        $isRedirect = $redirectStatus[$page['title']] ?? false;

        if ($redirectFilter === 'exclude') {
            return !$isRedirect;
        } elseif ($redirectFilter === 'only') {
            return $isRedirect;
        }
        return true;
    });
}

function getUserProfileInfo($username, $articleCount = 0) {
    $apiUrl = "https://justapedia.org/api.php?action=query&list=users&ususers=" . urlencode($username) . "&usprop=editcount|registration|groups|gender&format=json&origin=*";
    $response = @file_get_contents($apiUrl);
    if ($response === false) return ['error' => 'Failed to fetch user profile data.'];

    $data = json_decode($response, true);
    if (!isset($data['query']['users'][0])) return ['error' => 'User not found.'];

    $user = $data['query']['users'][0];
    return [
        'username' => $username,
        'editcount' => $user['editcount'] ?? 0,
        'registration' => isset($user['registration']) ? date('Y-m-d H:i:s', strtotime($user['registration'])) : 'N/A',
        'groups' => implode(', ', $user['groups'] ?? ['user']),
        'gender' => $user['gender'] ?? 'unknown',
        'articles_created' => $articleCount
    ];
}

function displayResults($username, $pages, $startDate, $endDate, $redirectFilter, $namespaceFilter, $articleCount) {
    $perPage = 50; // Show 50 per page
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $total = count($pages);
    $paginatedPages = array_values(array_slice($pages, ($page - 1) * $perPage, $perPage));

    echo "<div class='result-container'>";
    echo "<h2 class='result-header'>Contributions by " . htmlspecialchars($username) . "</h2>";
    echo "<div class='result-grid'>";

    $userInfo = getUserProfileInfo($username, $articleCount);
    echo "<div class='sidebar'>";
    echo "<div class='card'><h3 class='card-header'><span class='icon'>👤</span> User Profile</h3>";
    echo "<ul style='list-style-type: none; padding: 0;'>";
    echo "<li><strong>Username:</strong> " . htmlspecialchars($userInfo['username']) . "</li>";
    echo "<li><strong>Edit Count:</strong> " . htmlspecialchars($userInfo['editcount']) . "</li>";
    echo "<li><strong>Registration:</strong> " . htmlspecialchars($userInfo['registration']) . "</li>";
    echo "<li><strong>Groups:</strong> " . htmlspecialchars($userInfo['groups']) . "</li>";
    echo "<li><strong>Gender:</strong> " . htmlspecialchars($userInfo['gender']) . "</li>";
    echo "<li><strong>Total Articles Created:</strong> " . htmlspecialchars($userInfo['articles_created']) . "</li>";
    echo "</ul></div></div>";

    echo "<div class='main-content'>";
    if (!empty($paginatedPages)) {
        echo "<div class='card'><h3 class='card-header'><span class='icon'>📝</span> Articles Created (Page $page)</h3>";
        echo "<table class='styled-table'><thead><tr><th>Title</th><th>Creation Date</th><th>History</th></tr></thead><tbody>";
        foreach ($paginatedPages as $pageEntry) {
            $title = htmlspecialchars($pageEntry['title']);
            $creationDate = date('Y-m-d H:i:s', strtotime($pageEntry['timestamp']));
           $historyLink = "https://justapedia.org/w/index.php?title=" . rawurlencode($pageEntry['title']) . "&action=history";

           $articleLink = "https://justapedia.org/wiki/" . rawurlencode($pageEntry['title']);

            echo "<tr><td><a href='$articleLink' target='_blank'>$title</a></td><td>$creationDate</td><td><a href='$historyLink' target='_blank'>View History</a></td></tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<p class='info-message'>No articles found for this user.</p>";
    }

    if ($total > $perPage) {
        $totalPages = ceil($total / $perPage);
        echo "<div class='pagination'>";
        if ($page > 1) {
            echo "<button class='pagination-button' onclick=\"window.location.href='?user=" . urlencode($username) . "&redirects=" . urlencode($redirectFilter) . "&namespace=" . urlencode($namespaceFilter) . "&startDate=" . urlencode($startDate) . "&endDate=" . urlencode($endDate) . "&page=" . ($page - 1) . "'\">⬅️ Previous</button>";
        }
        echo "<span class='pagination-current'> Page $page of $totalPages </span>";
        if ($page < $totalPages) {
            echo "<button class='pagination-button' onclick=\"window.location.href='?user=" . urlencode($username) . "&redirects=" . urlencode($redirectFilter) . "&namespace=" . urlencode($namespaceFilter) . "&startDate=" . urlencode($startDate) . "&endDate=" . urlencode($endDate) . "&page=" . ($page + 1) . "'\">Next ➡️</button>";
        }
        echo "</div>";
    }

    echo "</div></div></div>";
}

if (isset($_GET['user'])) {
    $username = trim($_GET['user']);
    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';
    $redirectFilter = $_GET['redirects'] ?? 'exclude';
    $namespaceFilter = $_GET['namespace'] ?? 'main';

    $validation = validateInput($username, $startDate, $endDate);
    if ($validation !== true) {
        echo "<div class='container'><p class='error-message'>Error: " . htmlspecialchars($validation) . "</p></div>";
    } else {
        fetchUserData($username, $startDate, $endDate, $redirectFilter, $namespaceFilter);
    }
} else {
    echo "<div class='container'><p class='intro-message'>Welcome to Justapedia Tools. Enter a username to view contributions.</p></div>";
}
?>
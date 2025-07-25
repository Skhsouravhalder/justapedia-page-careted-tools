

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justapedia Tools - User Contributions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&family=Roboto:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/style.css">
</head>

<body>
<header>
    <h1>Justapedia Tools - User Contributions</h1>
</header>
<div class="container">
    <div class="form-container">
        <form method="GET" action="">
            <label for="user">Username:</label>
            <input type="text" name="user" id="user" placeholder="Enter username" required>

            <label for="namespace">Namespace:</label>
            <select name="namespace" id="namespace">
                <option value="main">Main namespace</option>
                <option value="all">All namespaces</option>
            </select>

            <label for="redirects">Redirects:</label>
            <select name="redirects" id="redirects">
                <option value="exclude">Exclude redirects</option>
                <option value="include">Include redirects</option>
                <option value="only">Only redirects</option>
            </select>

            <label for="startDate">Start Date (YYYY-MM-DD):</label>
            <input type="date" name="startDate" id="startDate">

            <label for="endDate">End Date (YYYY-MM-DD):</label>
            <input type="date" name="endDate" id="endDate">

            <button type="submit">Fetch Contributions</button>
        </form>
    </div>
</div>
<div class="container">
    <form method="GET" action="code/logic.php">
      <!-- all inputs -->
    </form>
  </div>
 <script src="./js/script.js"></script>
<footer class="footer">
  <p>© 2025 JPTools. Created by <a href="https://justapedia.org/wiki/User:Sourav" target="_blank">Sourav Halder</a>. 
    <a href="https://www.gnu.org/licenses/gpl-3.0.en.html" target="_blank">GPL 3.0 License</a>.
  </p>
</footer>

</body>
</html>

<?php

    // DATABASE CONNECTION
    $dsn = "mysql:host=localhost;dbname=chinook";
    $dbusername = "root";
    $dbpassword = "";

    try {

    $pdo = new PDO ($dsn, $dbusername, $dbpassword);
    $pdo -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    } catch (PDOException $e) {
        die("Database connection failed" . $e -> getMessage());
        
    }

    $action = "";
    $artistData = [];
    $searchedArtist = NULL;

    //SANITIZED INPUT BEFORE USE
    $albumId = filter_input(INPUT_GET , "albumId", FILTER_SANITIZE_SPECIAL_CHARS);
    $artistId = filter_input(INPUT_GET , "artistId", FILTER_SANITIZE_SPECIAL_CHARS);

    // POST REQUEST LOGIC
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

                //NEW TRACK REQUEST HANDLER
                if(isset($_POST["new_track"])) {

                    $newTrack = filter_input(INPUT_POST, "new_track", FILTER_SANITIZE_SPECIAL_CHARS);

                    try {

                        //GET LAST TRACKID
                        $query = "SELECT * FROM tracks ORDER BY TrackId DESC LIMIT 1";
                        $statement = $pdo -> query($query);
                        $lastTrack = $statement -> fetch(PDO :: FETCH_ASSOC);
                        $lastTrackId = $lastTrack["TrackId"];
                        $newTrackId = $lastTrackId + 1; 
                        $statement = null;


                        //INSERT NEW TRACK INTO ALBUM
                        $query = "INSERT INTO tracks (TrackId, Name, AlbumId) VALUES(:trackId, :name, :albumId)";
                        $statement = $pdo -> prepare($query);
                        $statement -> bindValue(":trackId", $newTrackId, PDO::PARAM_INT);
                        $statement -> bindValue(":name", $newTrack, PDO::PARAM_STR);
                        $statement -> bindValue(":albumId", $albumId, PDO::PARAM_INT);
                        $statement -> execute();

                        $pdo = null;
                        $statement2 = null;

                        header("location: ?action=album-update&albumId=" . $albumId);

                    } catch (PDOException $e){
                        die("Something went wrong" . $e -> getMessage());
                    }

                }

                // REMOVE TRACK REQUEST HANDLER
                if(isset($_POST["removed_track"])) {

                    $removedTrackId = $_POST["removed_track"];

                    try {
                        $query = "DELETE FROM tracks WHERE TrackId = :trackId";
                        $statement = $pdo -> prepare($query);
                        $statement -> bindValue(":trackId", $removedTrackId, PDO :: PARAM_INT);
                        $statement -> execute();

                        $statement = null;
                        $pdo = null;

                        header("location: index.php?action=album-update&albumId=" . $albumId);
                    } catch (PDOException $e) {
                        die("Something went wrong" . $e -> getMessage());
                    }
                }

                // UPDATE ALBUM REQUEST HANDLER
                if(isset($_POST["update_album"])) {  
                    
                    unset($_POST["update_album"]);
                    if(isset($_POST["album-title"])) {

                        $newTitle = filter_input(INPUT_POST, "album-title", FILTER_SANITIZE_SPECIAL_CHARS);

                        $query = "UPDATE albums SET albums.Title = :newTitle WHERE albums.AlbumId = :albumId ";
                        $statement = $pdo -> prepare($query);
                        $statement -> bindValue(":newTitle", $newTitle, PDO :: PARAM_STR);
                        $statement -> bindValue(":albumId", $albumId, PDO :: PARAM_INT);
                        $statement -> execute();

                        unset($_POST["album-title"]);
                    }

                    foreach($_POST as $trackId => $trackName){

                        $trackName = filter_var($trackName, FILTER_SANITIZE_STRING);

                        try {
                            $query = "UPDATE tracks SET Name = :trackName WHERE TrackId = :trackId";
                            $statement = $pdo -> prepare($query);
                            $statement -> bindValue(":trackName", $trackName, PDO :: PARAM_STR);
                            $statement -> bindValue(":trackId", $trackId, PDO :: PARAM_INT);
                            $statement -> execute();

                            } catch (PDOException $e) {
                                die("Something went wrong" . $e -> getMessage());
                        }
                    }
                        
                    $statement = null;
                    $pdo = null;
                    header("location: index.php?action=home");

                }

                //DELETE ALBUM REQUEST HANDLER
                if (isset($_POST["confirm-delete-album"])) {

                    $deletedAlbum = filter_input(INPUT_POST, "deleted-album-id", FILTER_SANITIZE_SPECIAL_CHARS);

                    $query = "SELECT ArtistId FROM albums WHERE AlbumId = :albumId";
                    $statement = $pdo -> prepare($query);
                    $statement -> bindValue(":albumId", $deletedAlbum, PDO :: PARAM_INT);
                    $statement -> execute();
                    $albumArtistId = $statement -> fetch(PDO :: FETCH_ASSOC)['ArtistId'];

                    $query = "SELECT artists.ArtistId, albums.AlbumId FROM artists 
                                INNER JOIN albums
                                ON albums.ArtistId = artists.ArtistId
                                WHERE artists.ArtistId = :artistId";
                    $statement = $pdo -> prepare($query);
                    $statement -> bindValue(":artistId", $albumArtistId, PDO :: PARAM_INT);
                    $statement -> execute();
                    $albumsByArtist = $statement -> fetchAll(PDO :: FETCH_ASSOC);
                    $number_of_albums = count($albumsByArtist);

                    if ( $number_of_albums > 1) {

                        $query = "DELETE albums, tracks FROM albums
                                    LEFT JOIN tracks
                                        ON tracks.AlbumId = albums.AlbumId
                                    WHERE albums.AlbumId = :albumId";
                        $statement = $pdo -> prepare($query);
                        $statement -> bindValue(":albumId", $deletedAlbum, PDO :: PARAM_INT);
                        $statement -> execute();

                        
                    } else {
                        
                        $query = "DELETE albums, tracks, artists FROM albums
                                    LEFT JOIN tracks
                                        ON tracks.AlbumId = albums.AlbumId
                                    LEFT JOIN artists
                                        ON artists.ArtistId = albums.ArtistId
                                    WHERE albums.AlbumId = :albumId";
                        $statement = $pdo -> prepare($query);
                        $statement -> bindValue(":albumId", $deletedAlbum, PDO :: PARAM_INT);
                        $statement -> execute();
                    }

                    header("location: index.php?action=home");


                }

                // ADD ALBUM REQUEST HANDLER
                if (isset($_POST["add-album"])) {

                    $albumTitle = filter_input(INPUT_POST, "album-title", FILTER_SANITIZE_SPECIAL_CHARS);
                    $artistName = filter_input(INPUT_POST, "artist", FILTER_SANITIZE_SPECIAL_CHARS);
                    $artistId = 0;

                    $query = "SELECT * FROM artists";
                    $statement = $pdo -> query($query);
                    $artists = $statement -> fetchAll(PDO :: FETCH_ASSOC);
                    $getArtistId = Null;

                    foreach($artists as $artist) {
                        if ($artist["Name"] == $artistName) {
                            $getArtistId = $artist["ArtistId"];
                        }
                    }

                    if (!$getArtistId) {

                        $query = "SELECT ArtistId FROM artists ORDER BY ArtistId  DESC LIMIT 1";
                        $statement = $pdo -> query($query);
                        $lastArtistId = $statement -> fetch(PDO :: FETCH_ASSOC)["ArtistId"];
                        $getArtistId = $lastArtistId + 1;

                        $query = "INSERT INTO artists (ArtistId, Name) VALUES(:artistId, :name)";
                        $statement = $pdo -> prepare($query);
                        $statement -> bindValue(":artistId", $getArtistId, PDO :: PARAM_INT);
                        $statement -> bindValue(":name", $artistName, PDO :: PARAM_STR);
                        $statement -> execute();

                    }

                    $query = "SELECT AlbumId FROM albums ORDER BY AlbumId DESC LIMIT 1";
                    $statement = $pdo -> query($query);
                    $lastAlbumId = $statement -> fetch(PDO :: FETCH_ASSOC)["AlbumId"];
                    $newAlbumId = $lastAlbumId + 1;

                    $query =  "INSERT INTO albums (AlbumId, Title, ArtistId) VALUES(:albumId, :title, :artistId)";
                    $statement = $pdo -> prepare($query);
                    $statement -> bindValue(":albumId", $newAlbumId, PDO :: PARAM_INT);
                    $statement -> bindValue(":title", $albumTitle, PDO :: PARAM_STR);
                    $statement -> bindValue(":artistId", $getArtistId, PDO :: PARAM_INT);
                    $statement -> execute();

                    unset($_POST["add-album"]);
                    unset($_POST["album-title"]);
                    unset($_POST["artist"]);


                    $query = "SELECT TrackId FROM tracks ORDER BY TrackId DESC LIMIT 1";
                    $statement = $pdo -> query($query);
                    $lastTrackId = $statement -> fetch(PDO :: FETCH_ASSOC)["TrackId"];
                    $submittedTracks = $_POST;
                    $incrementId = 1;

                    foreach($submittedTracks as $track){

                        $query =  "INSERT INTO tracks (TrackId, Name, AlbumId) VALUES(:trackId, :name, :albumId)";
                        $trackSanitized = filter_var($track, FILTER_SANITIZE_SPECIAL_CHARS);
                        $statement = $pdo -> prepare($query);
                        $statement -> bindValue(":trackId", $lastTrackId + $incrementId, PDO :: PARAM_INT);
                        $statement -> bindValue(":name", $trackSanitized, PDO :: PARAM_STR);
                        $statement -> bindValue(":albumId", $newAlbumId, PDO :: PARAM_INT);
                        $statement -> execute();
                        $incrementId++;
                    }
                    
                    $statement = NULL;
                    $pdo = NULL;

                    header("location: index.php?action=home");


                }
                
                // SEARCH ARTIST REQUEST HANDLER
                if (isset($_POST["search-artist"])) {

                    $artistName = filter_input(INPUT_POST, "artist", FILTER_SANITIZE_SPECIAL_CHARS);
                    $query = "SELECT * FROM artists WHERE Name = :artistName";
                    $statement = $pdo -> prepare($query);
                    $statement -> bindValue(":artistName", $artistName, PDO :: PARAM_STR);
                    $statement -> execute();
                    $searchedArtist = $statement -> fetch(PDO :: FETCH_ASSOC);
                    if ($searchedArtist) {

                        header("location: index.php?action=artist&artistId=" . $searchedArtist["ArtistId"]);
                    }else {
                        header("location: index.php?action=artist&artistId=None");

                    }

                }

                // UPDATE ARTIST REQUEST HANDLER
                if(isset($_POST["update-artist"])) {

                    $artistNewName = filter_input(INPUT_POST, "new-name", FILTER_SANITIZE_SPECIAL_CHARS);
                    $artistId = filter_input(INPUT_POST, "artistId", FILTER_SANITIZE_SPECIAL_CHARS);

                    $query = "UPDATE artists SET Name = :newName WHERE ArtistId = :artistId";
                    $statement = $pdo -> prepare($query);
                    $statement -> bindValue(":newName", $artistNewName, PDO :: PARAM_STR);
                    $statement -> bindValue(":artistId", $artistId, PDO :: PARAM_INT);
                    $statement -> execute();

                    $statement = null;
                    header("location: index.php?action=artist");



                }

                // DELETE ARTIST REQUEST HANDLER
                if(isset($_POST["confirm-delete"])) {
                    $deletedArtistId = filter_input(INPUT_POST, "deleted-artist-id", FILTER_SANITIZE_SPECIAL_CHARS);

                    if($deletedArtistId) {
                        $query = "SELECT artists.ArtistId, albums.AlbumId, tracks.TrackId
                                    FROM artists
                                    LEFT JOIN albums 
                                        ON artists.ArtistId = albums.ArtistId
                                    LEFT JOIN tracks
                                        ON albums.AlbumId = tracks.AlbumId
                                    WHERE artists.ArtistId = :artistId";

                        $statement = $pdo -> prepare($query);
                        $statement -> bindValue(":artistId", $deletedArtistId, PDO :: PARAM_INT);
                        $statement -> execute();
                        $data = $statement -> fetchAll(PDO :: FETCH_ASSOC);


                        if ( $data ) {

                            if (($data[0]["ArtistId"] ?? $data["ArtistId"]) && (!$data[0]["AlbumId"] ?? !$data["AlbumId"]) && (!$data[0]["TrackId"] ?? !$data["TrackId"])){

                                $query = "DELETE FROM artists WHERE ArtistId = :artistId";

                            } else if (($data[0]["ArtistId"] ?? $data["ArtistId"]) && ($data[0]["AlbumId"] ?? $data["AlbumId"]) && (!$data[0]["TrackId"] ?? !$data["TrackId"])){

                                $query = "DELETE artists, albums FROM artists
                                            INNER JOIN albums
                                                ON albums.ArtistId = artists.ArtistId
                                            WHERE artists.ArtistId = :artistId";

                            } else if (($data[0]["ArtistId"] ?? $data["ArtistId"]) && ($data[0]["AlbumId"] ?? $data["AlbumId"]) && ($data[0]["TrackId"] ?? $data["TrackId"])){

                                $query = "DELETE artists, albums, tracks FROM artists
                                            INNER JOIN albums
                                                ON albums.ArtistId = artists.ArtistId
                                            INNER JOIN tracks
                                                ON tracks.AlbumId = albums.AlbumId
                                            WHERE artists.ArtistId = :artistId";
                            
                            }

                            $statement = $pdo -> prepare($query);
                            $statement -> bindValue(":artistId", $deletedArtistId, PDO :: PARAM_INT);
                            $statement -> execute();

                        }

                        $statement = null;
                        $pdo = null;
                    }

                    header("location: index.php?action=artist");
                }  

    } else {

        // GET REQUEST LOGIC

        //HOME DISPLAY

        $action = $_GET["action"] ?? "home";

        if ($action == "home") {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
                'options' => [
                    'default' => 1,  
                    'min_range' => 1 
                ]
            ]);

            $pagination_start = ($page - 1) * 10;

            $query = "SELECT * 
                        FROM albums
                        INNER JOIN artists 
                        ON albums.ArtistId = artists.ArtistId
                        ORDER BY AlbumId
                        DESC   
                        LIMIT :pagination_start, :count_per_page";

            $statement = $pdo->prepare($query);
            $statement->bindValue(':pagination_start', $pagination_start, PDO::PARAM_INT);
            $statement->bindValue(':count_per_page', 10, PDO::PARAM_INT);
            $statement->execute();
            $allAlbum = $statement->fetchAll(PDO::FETCH_ASSOC);

            $count_albums = "SELECT COUNT(*) FROM albums";
            $statement = $pdo->query($count_albums);
            $number_of_albums = $statement->fetchColumn();

        }

        // ALBUM - VIEW, UPDATE AND DELETE
        if ($action == "album-view" || $action == "album-update" || $action == "album-delete" ) {
                    
            if($albumId) {

                $query = "SELECT albums.ArtistId as artistId, albums.Title as albumName, tracks.Name as trackName, tracks.TrackId as trackId, artists.Name as artistName 
                            FROM albums
                            INNER JOIN tracks ON albums.AlbumId = tracks.AlbumId
                            INNER JOIN artists ON albums.ArtistId = artists.ArtistId
                            WHERE albums.AlbumId = :AlbumId";

                $statement = $pdo -> prepare($query);
                $statement -> bindValue(":AlbumId", $albumId, PDO::PARAM_INT);
                $statement ->execute();
                $albumData = $statement -> fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($albumData)) {
                    $query2 = "SELECT albums.Title, albums.ArtistId FROM albums WHERE albums.AlbumId = :albumId";
                    $statement2 = $pdo -> prepare($query2);
                    $statement2 -> bindValue(":albumId", $albumId, PDO :: PARAM_INT);
                    $statement2 -> execute();
                    $emptyAlbum = $statement2 -> fetch(PDO :: FETCH_ASSOC);

                }
            }
        }

        // ADD ALBUM 
        if ($action == "album-add") {

            $query = "SELECT * FROM artists";
            $statement = $pdo -> query($query);
            $artists = $statement -> fetchAll(PDO :: FETCH_ASSOC);
        }

        // ARTIST
        if ($action == "artist") {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
                'options' => [
                    'default' => 1,  
                    'min_range' => 1 
                ]
            ]);

            $pagination_start = ($page - 1) * 10;
            $getArtistId = filter_input(INPUT_GET, "artistId", FILTER_SANITIZE_SPECIAL_CHARS);
            if (!$getArtistId || $getArtistId != "None"){

                if ($getArtistId) {
                    $query = "SELECT * FROM artists WHERE ArtistId = :artistId";
                    $statement = $pdo -> prepare($query);
                    $statement -> bindValue(":artistId", $getArtistId, PDO :: PARAM_STR);
                    $statement -> execute();
                    $searchedArtist = $statement -> fetch(PDO :: FETCH_ASSOC);
    
                }else {
    
                    $query = "SELECT * FROM artists
                                ORDER BY ArtistId
                                DESC   
                                LIMIT :pagination_start, :count_per_page";
        
                    $statement = $pdo->prepare($query);
                    $statement->bindValue(':pagination_start', $pagination_start, PDO::PARAM_INT);
                    $statement->bindValue(':count_per_page', 10, PDO::PARAM_INT);
                    $statement->execute();
                    $artistData = $statement->fetchAll(PDO::FETCH_ASSOC);
        
                    if(count($artistData) > 1){
        
                        $count_artists = "SELECT COUNT(*) FROM artists";
                        $statement = $pdo->query($count_artists);
                        $number_of_artists = $statement->fetchColumn();
                    }
                }
            }
            
        }

        // ARTIST - VIEW, UPDATE AND DELETE
        if ($action == "artist-view" || $action == "artist-update" || $action == "artist-delete") {

            $artistId = filter_input(INPUT_GET, "artistId", FILTER_SANITIZE_SPECIAL_CHARS);

            if($artistId) {
                $query = "SELECT artists.Name as artist, artists.ArtistId as artistId, albums.Title as albumTitle, albums.AlbumId as albumId
                            FROM artists
                            INNER JOIN albums
                            ON artists.ArtistId = albums.ArtistId
                            WHERE artists.ArtistId = :artistId";
                            
                $statement = $pdo -> prepare($query);
                $statement -> bindValue(":artistId",$artistId, PDO :: PARAM_INT);
                $statement -> execute();
                $artist = $statement -> fetchAll(PDO :: FETCH_ASSOC);

                if(empty($artist)) {

                    $query = "SELECT * FROM artists WHERE ArtistId = :artistId";
                    $statement = $pdo -> prepare($query);
                    $statement -> bindValue(":artistId", $artistId, PDO :: PARAM_INT);
                    $statement -> execute();
                    $noAlbumArtist = $statement -> fetch(PDO :: FETCH_ASSOC);  

                }
            }

        }
    }

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="style.css" rel="stylesheet">
    <script src="index.js"></script>

</head>
<body>
    <div class="navbar-container">
        <a href="?action=home"><img src="chinook-logo.png" alt="chinook" id="logo"></a>
        <div class="navbar-items-container">
            <a href="?action=home" class="navbar-items">Home</a>
            <a href="?action=album-add" class="navbar-items">Album</a>
            <a href="?action=artist" class="navbar-items">Artist</a>
        </div>
    </div>
    <?php if ($action == "home"): ?>
                
        <div class="home-img-container"></div>
        <h2 id="albums">ALBUMS</h2>
        <section class="albums-section">
            <div class="header-container">
                <div class="header-items">Album</div>
                <div class="header-items">Artist</div>
                <div class="header-items" id="action-section">Actions</div>
            </div>

            <?php foreach ($allAlbum as $album): ?>
                <div class='album-container'>
                    <div class='album-items'> <?php  echo htmlspecialchars($album['Title']) ?> </div>
                    <div class='album-items'> <?php  echo htmlspecialchars($album['Name']) ?> </div>
                    <div class='album-items action-btn-container'>
                        <a href='?action=album-view&albumId=<?php echo htmlspecialchars($album['AlbumId']) ?>'   class='action-btn' id='btn-view'>View</a>
                        <a href='?action=album-update&albumId=<?php echo htmlspecialchars($album['AlbumId']) ?>' class='action-btn' id='btn-update'>Update</a>
                        <a href='?action=album-delete&albumId=<?php echo htmlspecialchars($album['AlbumId']) ?>' class='action-btn' id='btn-delete'>Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="pagination">
                <?php
                    $total_pages = ceil($number_of_albums / 10);
                    if ($page > 1) {
                        echo "<a class='pagination-btn' href='?action=home&page=" . ($page - 1) . "'>Previous</a>";
                    }
                    echo $page . " of " . $total_pages;
                    if ($page < $total_pages) {
                        echo "<a class='pagination-btn next-btn' href='?action=home&page=" . ($page + 1) . "'>Next</a>";
                    }
                ?>
            </div>
        </section>

    <?php endif; ?>

    <?php if($action == "album-view" || $action == "album-delete"): ?>

        <h3 class="headers">ALBUM TITLE &nbsp; &nbsp;<span class="album-title-h"><?php echo htmlspecialchars($albumData[0]["albumName"] ?? $emptyAlbum["Title"]) ?></span></h3>
        <div class="tracks-container">
            <h4 class="track-title">Tracks</h4>
            <?php
                if (!empty($albumData)){

                    $count = 1;
                    foreach($albumData as $track){
                        echo "<div class='track-container'>" . $count . ". " . htmlspecialchars($track['trackName']) . "</div>";
                        $count++;
                    }
                } else {
                    echo "<div class='track-container empty-album'>Album has no track added yet....</div>";
                }
            ?>
        </div>
    <?php endif; ?>


    <?php if($action == "album-update"): ?>

        <h3 class="headers">
        ALBUM TITLE &nbsp; &nbsp;
        <span class="album-title-h">
        <?php echo htmlspecialchars($albumData[0]["albumName"] ?? $emptyAlbum["Title"])?>
        </span>
            <button class="edit-album-title" onclick="displayField()">edit?</button>
        </h3>

        <form action="?albumId=<?php echo htmlspecialchars($albumId) ?>" method="post" class="update-form" id="update-form">
            <?php if (!empty($albumData)):?> 
                <input type="text" name="album-title" value="<?php echo htmlspecialchars($albumData[0]["albumName"])?>" id="album-title-field" class="form-field album-title-field hide-form">
                <div class="update-tracks-header">Tracks</div>

                <?php foreach($albumData as $track): ?>
                    <input type='text' name="<?php echo htmlspecialchars($track['trackId'])?>" value="<?php echo htmlspecialchars($track["trackName"])?>" class='form-field'>
                    <button type='submit' name='removed_track' value='<?php echo htmlspecialchars($track['trackId'])?>' onclick="return confirm('Are you sure you want to delete this track?')" class="remove-track">Remove</button>
                <?php endforeach; ?>

                <div id="new_track"></div>
                <div class="update-form-btn" id="update-form-btn">
                    <button type="submit" name="update_album" class="form-update" id="form-update-btn">Update</button>
                    <button type="button" name="add-track" class="form-add-track" id="form-add-track-btn" onclick="addTrack('new_track')">Add track</button>
                </div>

            <?php else: ?>

                <input type="text" name="album-title" value="<?php echo htmlspecialchars($emptyAlbum["Title"])?>" id="album-title-field" class="form-field album-title-field hide-form">
                <h3 id="message-empty">Album has no track added yet....</h3>
                <div id="new_track"></div>
                <button type="button" name="add-track" class="form-add-track form-empty" id="form-add-track-btn" onclick="addTrack('new_track', 'message-empty')">Add track</button>
            <?php endif; ?>
        </form>

    <?php endif; ?>


    <?php if ($action == "album-delete"): ?>
        <form action="" method="post" class="delete-album-form">
            <input type="hidden" name="deleted-album-id" value="<?php echo $albumId ?>">
            <p class="delete-prompt">Are you sure to delete this album?</p>
            <button class="delete-album-btn" id="confirm-delete-btn" type="submit" name="confirm-delete-album">Yes</button>
            <a class="delete-album-btn" href="?action=home">No</a>
        </form>
    <?php endif; ?>


    <?php if ($action == "album-add"): ?>
        <h3 class="headers">CREATE ALBUM</h3>
        <form action="?action=home" method="post" class="add-album-form">
            <input type="text" name="artist" list="artists" class="form-field add-album" placeholder="Select/Enter a new artist" required>
            <datalist type="text" id="artists">
                <?php foreach($artists as $artist): ?>
                    <option value="<?php echo $artist["Name"] ?>"  class="list-options">
                <?php endforeach; ?>
            </datalist>
            <input type="text" class="form-field add-album" name="album-title" placeholder="Enter album title" required>
            <input type="text" name="newTrack-0" placeholder="Enter track title" class="form-field add-track-field" required>
            <div id="newTrack"></div>
            <div class="update-form-btn " id="add-album">
                <button type="submit" name="add-album" class="form-update" id="form-update-btn">Create album</button>
                <button type="button" name="add-track" class="form-add-track" id="form-add-track-btn" onclick="addTrack2()">Add track</button>
            </div>
        </form>

    <?php endif; ?>

    <?php if($action == "artist"): ?>
                            
        <div class="artist-page">
            <div class="search-artist-container">
                <h3 class="headers search-artist"></h3>
                <form action="" method="post" class="artist-form">
                    <input type="text" placeholder="Enter artist name" name="artist" class="form-field add-artist-form-field" required>
                    <button type="submit" class="search-artist-btn" name="search-artist">SEARCH</button>
                </form>
            </div>
            <div class="view-artists-container">
                <h3 class="headers">ARTISTS</h3>
                <div class="artists-container">
                    <?php if(!empty($artistData)): ?> 
                        <?php foreach($artistData as $artist):?>
                            <div class="artist-row">
                                <div class="artist-name"><?php echo htmlspecialchars($artist["Name"]) ?></div>
                                <div class="action-btn-container artist-view">
                                    <a href='?action=artist-view&artistId=<?php echo htmlspecialchars($artist['ArtistId']) ?>'   class='artist-page-btn'>View</a>
                                    <a href='?action=artist-update&artistId=<?php echo htmlspecialchars($artist['ArtistId']) ?>' class='artist-page-btn'>Update</a>
                                    <a href='?action=artist-delete&artistId=<?php echo htmlspecialchars($artist['ArtistId']) ?>' class='artist-page-btn'>Delete</a>
                                </div>
                            </div>
                        <?php endforeach ?>
                        <div class="pagination">
                            <?php
                                $total_pages = ceil($number_of_artists / 10);
                                if ($page > 1) {
                                    echo "<a class='pagination-btn' href='?action=artist&page=" . ($page - 1) . "'>Previous</a>";
                                }
                                echo $page . " of " . $total_pages;
                                if ($page < $total_pages) {
                                    echo "<a class='pagination-btn next-btn' href='?action=artist&page=" . ($page + 1) . "'>Next</a>";
                                }
                            ?>
                        </div>
                    <?php elseif ($searchedArtist && !empty($searchedArtist)): ?>
                        <div class="artist-row">
                            <div class="artist-name"><?php echo htmlspecialchars($searchedArtist["Name"]) ?></div>
                            <div class="action-btn-container artist-view">
                                <a href='?action=artist-view&artistId=<?php echo htmlspecialchars($searchedArtist['ArtistId']) ?>'   class='artist-page-btn'>View</a>
                                <a href='?action=artist-update&artistId=<?php echo htmlspecialchars($searchedArtist['ArtistId']) ?>' class='artist-page-btn'>Update</a>
                                <a href='?action=artist-delete&artistId=<?php echo htmlspecialchars($searchedArtist['ArtistId']) ?>' class='artist-page-btn'>Delete</a>
                            </div>
                        </div>
                    <?php elseif (empty($artistData) && empty($searchedArtist)): ?>
                        <h3 id="search-artist-message">Artist with name not found....</h3>
                    <?php endif;?>
                </div>                    
            </div>
        </div>
    <?php endif; ?>
    
    <?php if($action == "artist-view"): ?>
        <h3 class="headers">ARTIST &nbsp; &nbsp;<span class="album-title-h"><?php echo htmlspecialchars($artist[0]["artist"] ?? $noAlbumArtist["Name"]) ?></span></h3>
        <div class="tracks-container">
            <h4 class="track-title">Albums</h4>
                <?php if (!empty($artist)): ?>

                    <?php $count = 1?>
                    <?php foreach($artist as $album): ?>
                        <div class='album-row'>
                            <div><?php echo $count . ". " .  htmlspecialchars($album["albumTitle"])?></div>
                            <div class='action-btn-container artist-view'>
                            <a href='?action=album-view&albumId=<?php echo $album["albumId"]?>' class='artist-page-btn'>View</a>
                            <a href='?action=album-update&albumId=<?php echo $album["albumId"]?>' class='artist-page-btn'>Update</a>
                            <a href='?action=album-delete&albumId=<?php echo $album["albumId"]?>' class='artist-page-btn'>Delete</a>

                            </div>
                        </div>
                        <?php $count++?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class='track-container empty-album'>Artist has no album yet....</div>
                <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if($action == "artist-update"): ?>

        <h3 class="headers">ARTIST &nbsp; &nbsp;<span class="album-title-h"><?php echo htmlspecialchars($artist[0]["artist"] ?? $noAlbumArtist["Name"])?></span></h3>

        <form action="index.php" method="post" class="artist-form">
            <input type="text" class="artist-update-field" name="new-name" placeholder="Enter new name" required>
            <input type="hidden" class="artist-update-field" name="artistId" value="<?php echo htmlspecialchars($artist[0]["artistId"] ?? $noAlbumArtist["ArtistId"]) ?>">
            <button type="submit" class="add-album-btn" name="update-artist">UPDATE</button>
        </form>
    <?php endif; ?>

    <?php if($action == "artist-delete"): ?>

        <h3 class="headers">ARTIST &nbsp; &nbsp;<span class="album-title-h"><?php echo htmlspecialchars($artist[0]["artist"] ?? $noAlbumArtist["Name"]) ?></span></h3>
        <div class="tracks-container">
            <h4 class="track-title">Albums</h4>
            <?php
                if (!empty($artist)){
                    $count = 1;
                    foreach($artist as $album){
                        echo "<div class='album-row'>";
                            echo "<div>" . $count . ". " . htmlspecialchars($album['albumTitle']) . "</div>";
                        echo "</div>";
                        $count++;
                    }
                } else {
                    echo "<div class='track-container empty-album'>Artist has no album yet....</div>";
                }
            ?>

        </div>
        <form action="" method="post" class="delete-album-form">
            <input type="hidden" name="deleted-artist-id" value="<?php echo $artistId ?>">
            <p class="delete-prompt">Are you sure to delete this artist along with its albums?</p>
            <button class="delete-album-btn" id="confirm-delete-btn" type="submit" name="confirm-delete">Yes</button>
            <a class="delete-album-btn" href="?action=artist">No</a>
        </form>

    <?php endif; ?>
</body>
</html>

<?php
// ======================================================================
// SECTION 1: CONFIGURATION & DATABASE CONNECTION
// ======================================================================

// IMPORTANT: Replace with your actual database credentials
$servername = "localhost";
$username = "legasifu_test";
$password = "Hakimi2906@";
$dbname = "legasifu_wp785";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// ======================================================================
// SECTION 2: API LOGIC (Handles requests from JavaScript)
// ======================================================================

if (isset($_GET['action']) && $_GET['action'] === 'get_gallery') {
    header('Content-Type: application/json');
    $galleryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $response = ['error' => 'Invalid ID'];

    if ($galleryId > 0) {
        $stmt = $conn->prepare("SELECT title, event_date, description FROM galleries WHERE id = ?");
        $stmt->bind_param("i", $galleryId);
        $stmt->execute();
        $galleryData = $stmt->get_result()->fetch_assoc();

        if ($galleryData) {
            $imgStmt = $conn->prepare("SELECT image_path FROM gallery_images WHERE gallery_id = ? ORDER BY id ASC");
            $imgStmt->bind_param("i", $galleryId);
            $imgStmt->execute();
            $imgResult = $imgStmt->get_result();
            
            $images = [];
            while ($row = $imgResult->fetch_assoc()) {
                $images[] = $row['image_path'];
            }
            
            $galleryData['images'] = $images;
            $response = $galleryData;
        } else {
            $response = ['error' => 'Gallery not found'];
        }
    }
    
    echo json_encode($response);
    $conn->close();
    exit();
}

// ======================================================================
// SECTION 3: ADMIN PANEL LOGIC (Handles form submission)
// ======================================================================

$admin_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_gallery'])) {
    
    $category = $_POST['category'];
    $title = $_POST['title'];
    $date = $_POST['date'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO galleries (category, title, event_date, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $category, $title, $date, $description);
    
    if ($stmt->execute()) {
        $gallery_id = $stmt->insert_id;
        $stmt->close();

        $uploadDir = 'img/gallery/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $imageCount = count($_FILES['gallery_images']['name']);
        for ($i = 0; $i < $imageCount; $i++) {
            if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = uniqid() . '-' . basename($_FILES['gallery_images']['name'][$i]);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], $targetPath)) {
                    $is_thumbnail = ($i == 0);
                    $imgStmt = $conn->prepare("INSERT INTO gallery_images (gallery_id, image_path, is_thumbnail) VALUES (?, ?, ?)");
                    $imgStmt->bind_param("isi", $gallery_id, $targetPath, $is_thumbnail);
                    $imgStmt->execute();
                    $imgStmt->close();
                }
            }
        }
        $admin_message = "Gallery created successfully!";
    } else {
        $admin_message = "Error creating gallery: " . $stmt->error;
    }
}

// ======================================================================
// SECTION 4: PAGE ROUTING (Decide whether to show Admin or Public page)
// ======================================================================

if (isset($_GET['page']) && $_GET['page'] === 'admin0b7602f1') {
// --- START ADMIN PAGE HTML ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Gallery</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-10 p-8 bg-white max-w-3xl rounded shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Add New Gallery</h1>
            <a href="gallery.php" class="text-blue-500 hover:underline">&larr; Back to Public Gallery</a>
        </div>
        
        <?php if ($admin_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <span><?php echo htmlspecialchars($admin_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="gallery.php?page=admin0b7602f1" method="post" enctype="multipart/form-data">
            <input type="hidden" name="submit_gallery" value="1">
            <div class="mb-4">
                <label for="title" class="block text-gray-700 font-bold mb-2">Title:</label>
                <input type="text" id="title" name="title" class="shadow border rounded w-full py-2 px-3" required>
            </div>
            <div class="mb-4">
                <label for="date" class="block text-gray-700 font-bold mb-2">Date / Location:</label>
                <input type="text" id="date" name="date" class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label for="category" class="block text-gray-700 font-bold mb-2">Category:</label>
                <select id="category" name="category" class="shadow border rounded w-full py-2 px-3" required>
                    <option value="project">Project</option>
                    <option value="event">Event</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="description" class="block text-gray-700 font-bold mb-2">Description:</label>
                <textarea id="description" name="description" rows="5" class="shadow border rounded w-full py-2 px-3"></textarea>
            </div>
            <div class="mb-6">
                <label for="gallery_images" class="block text-gray-700 font-bold mb-2">Images (first will be thumbnail):</label>
                <input type="file" id="gallery_images" name="gallery_images[]" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0" required>
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Gallery
            </button>
        </form>
    </div>
</body>
</html>
<?php
// --- END ADMIN PAGE HTML ---

} else {

// --- START PUBLIC GALLERY PAGE HTML ---

// **Remove this block once the page is working correctly**
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getGalleries($conn, $category) {
    if (!$conn || $conn->connect_error) { return []; }
    $sql = "SELECT g.id, g.title, g.event_date, gi.image_path as thumbnail
            FROM galleries g
            LEFT JOIN gallery_images gi ON g.id = gi.gallery_id AND gi.is_thumbnail = 1
            WHERE g.category = ? ORDER BY g.created_at DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) { die("Error preparing statement: " . $conn->error); }
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->fetch_all(MYSQLI_ASSOC);
}

$projects = getGalleries($conn, 'project');
$events = getGalleries($conn, 'event');
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Legasi Futura</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/icon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="img/icon/favicon.svg" />
    <link rel="shortcut icon" href="img/icon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="img/icon/apple-touch-icon.png" />
    <link rel="manifest" href="img/icon/site.webmanifest" />
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gallery-card img { transition: transform 0.3s ease-in-out; }
        #modal-slider img { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 100%; max-height: 100%; object-fit: contain; display: none; }
        #modal-slider img.is-active { display: block; }
    </style>
</head>
<body class="bg-white text-black">

    <header id="main-header" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-black">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="index.html" class="flex items-center space-x-2">
                    <img src="img/logo.webp" alt="Legasi Futura Logo" class="h-10">
                </a>
                <nav class="hidden lg:flex items-center space-x-6">
                    <a href="index.html" class="text-white/90 uppercase text-sm font-medium tracking-wider transition-colors duration-300 hover:text-white">Home</a>
                    <div class="relative group">
                        <button class="flex items-center space-x-1 text-white/90 uppercase text-sm font-medium tracking-wider transition-colors duration-300 hover:text-white">
                            <span>Who We Are</span>
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-48 bg-black/80 backdrop-blur-sm rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-all duration-300 invisible group-hover:visible pt-2 pb-2">
                            <a href="vision-mission.html" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Vision & Mission</a>
                            <a href="policies.html" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Our Policies</a>
                            <a href="cert.html" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Licenses & Certifications</a>
                            <a href="track-record.html" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Track Record</a>
                        </div>
                    </div>
                    <div class="relative group">
                        <button class="flex items-center space-x-1 text-white/90 uppercase text-sm font-medium tracking-wider transition-colors duration-300 hover:text-white">
                            <span>What We Do</span>
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-48 bg-black/80 backdrop-blur-sm rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-all duration-300 invisible group-hover:visible pt-2 pb-2">
                            <a href="what-we-do.html" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Overview</a>
                            <a href="gallery.php" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Gallery</a>
                        </div>
                    </div>
                    <a href="contact-us.html" class="text-white/90 uppercase text-sm font-medium tracking-wider transition-colors duration-300 hover:text-white">Contact Us</a>
                    <a href="career-internship.html" class="text-white/90 uppercase text-sm font-medium tracking-wider transition-colors duration-300 hover:text-white">Career / Internship</a>
                </nav>
                <div class="lg:hidden flex items-center space-x-4">
                     <button class="text-white/90 hover:text-white transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                    <button id="burger-menu" class="text-white focus:outline-none">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div id="mobile-menu" class="hidden lg:hidden fixed inset-0 bg-gray-900/95 backdrop-blur-md z-40 pt-24">
        <nav class="flex flex-col items-center space-y-8">
            <a href="index.html" class="text-white/90 uppercase text-lg font-medium tracking-wider transition-colors duration-300 hover:text-white">Home</a>
            <div class="w-full text-center">
                <button class="mobile-menu-toggle text-white/90 uppercase text-lg font-medium tracking-wider transition-colors duration-300 hover:text-white inline-flex items-center">
                    <span>Who We Are</span>
                    <svg class="w-5 h-5 ml-2 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div class="mobile-submenu hidden flex flex-col items-center space-y-4 pt-4">
                    <a href="vision-mission.html" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Vision & Mission</a>
                    <a href="policies.html" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Our Policies</a>
                    <a href="cert.html" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Licenses & Certifications</a>
                    <a href="track-record.html" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Track Record</a>
                </div>
            </div>
            <div class="w-full text-center">
                <button class="mobile-menu-toggle text-white/90 uppercase text-lg font-medium tracking-wider transition-colors duration-300 hover:text-white inline-flex items-center">
                    <span>What We Do</span>
                    <svg class="w-5 h-5 ml-2 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div class="mobile-submenu hidden flex flex-col items-center space-y-4 pt-4">
                    <a href="what-we-do.html" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Overview</a>
                    <a href="gallery.php" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Gallery</a>
                </div>
            </div>
            <a href="contact-us.html" class="text-white/90 uppercase text-lg font-medium tracking-wider transition-colors duration-300 hover:text-white">Contact Us</a>
            <a href="career-internship.html" class="text-white/90 uppercase text-lg font-medium tracking-wider transition-colors duration-300 hover:text-white">Career / Internship</a>
        </nav>
    </div>

    <div id="main-container">
        <main class="pt-32 pb-20">
            <section id="project-gallery" class="py-20 bg-gray-50">
                <div class="container mx-auto px-6">
                    <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Project Gallery</h2>
                    <div class="relative">
                        <div id="project-slider-container" class="overflow-hidden">
                            <div id="project-slider-track" class="flex transition-transform duration-500 ease-in-out">
                                <?php foreach($projects as $row): ?>
                                <div class="gallery-card w-full sm:w-1/2 md:w-1/3 lg:w-1/4 flex-shrink-0 p-4" data-gallery-id="<?php echo $row['id']; ?>">
                                    <div class="relative overflow-hidden rounded-lg shadow-lg cursor-pointer group">
                                        <img src="<?php echo htmlspecialchars($row['thumbnail'] ?? 'img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="w-full h-72 object-cover group-hover:scale-105">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                                        <div class="absolute bottom-0 left-0 p-4">
                                            <h3 class="text-white text-xl font-bold"><?php echo htmlspecialchars($row['title']); ?></h3>
                                            <p class="text-white/90 text-sm"><?php echo htmlspecialchars($row['event_date']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button id="project-slider-prev" class="absolute left-0 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full hover:bg-black/80 z-10"> &lt; </button>
                        <button id="project-slider-next" class="absolute right-0 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full hover:bg-black/80 z-10"> &gt; </button>
                    </div>
                </div>
            </section>

            <section id="event-gallery" class="py-20 bg-white">
                <div class="container mx-auto px-6">
                    <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Event Gallery</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                        <?php foreach($events as $row): ?>
                        <div class="gallery-card" data-gallery-id="<?php echo $row['id']; ?>">
                            <div class="relative overflow-hidden rounded-lg shadow-lg cursor-pointer group">
                                <img src="<?php echo htmlspecialchars($row['thumbnail'] ?? 'img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="w-full h-72 object-cover group-hover:scale-105">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                                <div class="absolute bottom-0 left-0 p-4">
                                    <h3 class="text-white text-xl font-bold"><?php echo htmlspecialchars($row['title']); ?></h3>
                                    <p class="text-white/90 text-sm"><?php echo htmlspecialchars($row['event_date']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </main>
        
        <footer class="bg-gray-800 text-white py-8">
            <div class="container mx-auto px-6">
                <div class="text-center md:text-left">
                    <div class="mb-6 md:mb-0">
                        <p class="text-sm text-gray-400 mt-4">1st Floor, Lot 2161 Block 5,</p>
                        <p class="text-sm text-gray-400">Jalan Saberkas Utama, Jalan Pujut-Lutong,</p>
                        <p class="text-sm text-gray-400">98000 Miri, Sarawak, Malaysia.</p>
                        <div class="flex space-x-4 mt-4 justify-center md:justify-start">
                            <a href="https://www.linkedin.com/company/legasifutura/" class="text-gray-400 hover:text-white transition-colors duration-300">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd"/></svg>
                            </a>
                            <a href="https://www.facebook.com/p/Legasi-Futura-100094370892508/" class="text-gray-400 hover:text-white transition-colors duration-300">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <div class="text-center text-white text-sm py-6 bg-black">
            &copy; 2025 Legasi Futura Sdn. Bhd. All rights reserved.
        </div>
    </div>

    <a href="https://wa.me/60138626042" class="float" target="_blank">
        <i class="fa fa-whatsapp my-float"></i>
    </a>
    <a id="back-to-top-btn" href="#" class="hidden fixed bottom-28 right-10 bg-blue-900 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition-all duration-300 z-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
    </a>

    <div id="gallery-modal" class="hidden fixed inset-0 bg-black bg-opacity-80 z-[100] flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col md:flex-row">
            <div class="w-full md:w-3/5 h-[60vh] md:h-full relative bg-black rounded-t-lg md:rounded-l-lg md:rounded-t-none overflow-hidden">
                <div id="modal-slider" class="w-full h-full relative">
                </div>
                <button id="slider-prev" class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full hover:bg-black/80 transition-colors text-lg leading-none">&lt;</button>
                <button id="slider-next" class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full hover:bg-black/80 transition-colors text-lg leading-none">&gt;</button>
            </div>

            <div class="w-full md:w-2/5 p-6 flex flex-col flex-grow overflow-y-auto">
                <h3 id="modal-title" class="text-2xl font-bold text-gray-900"></h3>
                <p id="modal-date" class="text-sm text-gray-500 mt-1 mb-4"></p>
                <p id="modal-description" class="text-gray-700 text-justify"></p>
            </div>
        </div>
        <button id="close-gallery-modal" class="absolute top-4 right-4 text-white text-3xl font-light hover:text-gray-300">&times;</button>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        
        // --- HEADER & MENU LOGIC ---
        const burgerMenuBtn = document.getElementById('burger-menu');
        const mobileMenu = document.getElementById('mobile-menu');
        const mainHeader = document.getElementById('main-header');
        const backToTopBtn = document.getElementById('back-to-top-btn');

        if (burgerMenuBtn && mobileMenu) {
            burgerMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
        
        if (mainHeader) {
            let lastScrollY = window.scrollY;
            window.addEventListener('scroll', () => {
                if (window.scrollY > lastScrollY && window.scrollY > 150) {
                    mainHeader.classList.add('-translate-y-full');
                } else {
                    mainHeader.classList.remove('-translate-y-full');
                }
                lastScrollY = window.scrollY <= 0 ? 0 : window.scrollY; 
                
                if (backToTopBtn) {
                    if (window.scrollY > 300) {
                        backToTopBtn.classList.remove('hidden');
                    } else {
                        backToTopBtn.classList.add('hidden');
                    }
                }
            });
        }

        if (backToTopBtn) {
            backToTopBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        document.querySelectorAll('.mobile-menu-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                toggle.nextElementSibling.classList.toggle('hidden');
                toggle.querySelector('svg').classList.toggle('rotate-180');
            });
        });

        // --- PROJECT SLIDER LOGIC ---
        const projectSliderTrack = document.getElementById('project-slider-track');
        const projectSliderPrevBtn = document.getElementById('project-slider-prev');
        const projectSliderNextBtn = document.getElementById('project-slider-next');
        const projectCards = document.querySelectorAll('#project-slider-track .gallery-card');

        if (projectSliderTrack && projectCards.length > 0) {
            let currentIndex = 0;
            
            function getItemsPerView() {
                if (window.innerWidth >= 1024) return 4;
                if (window.innerWidth >= 768) return 3;
                if (window.innerWidth >= 640) return 2;
                return 1;
            }

            function updateSlider() {
                const itemsPerView = getItemsPerView();
                const maxIndex = projectCards.length > itemsPerView ? projectCards.length - itemsPerView : 0;
                
                if (currentIndex > maxIndex) currentIndex = maxIndex;
                if (currentIndex < 0) currentIndex = 0;

                const cardWidthPercentage = 100 / itemsPerView;
                projectSliderTrack.style.transform = `translateX(-${currentIndex * cardWidthPercentage}%)`;

                projectSliderPrevBtn.style.display = currentIndex > 0 ? 'block' : 'none';
                projectSliderNextBtn.style.display = currentIndex < maxIndex ? 'block' : 'none';
            }

            projectSliderNextBtn.addEventListener('click', () => {
                const itemsPerView = getItemsPerView();
                const maxIndex = projectCards.length > itemsPerView ? projectCards.length - itemsPerView : 0;
                if (currentIndex < maxIndex) {
                    currentIndex++;
                    updateSlider();
                }
            });

            projectSliderPrevBtn.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateSlider();
                }
            });
            
            window.addEventListener('resize', updateSlider);
            updateSlider();
        }

        // --- MODAL POPUP LOGIC ---
        const allGalleryCards = document.querySelectorAll('.gallery-card');
        const modal = document.getElementById('gallery-modal');
        if (!modal) {
            console.error("Fatal Error: The #gallery-modal element was not found.");
            return;
        }
        const closeModalBtn = document.getElementById('close-gallery-modal');
        const slider = document.getElementById('modal-slider');
        const modalTitle = document.getElementById('modal-title');
        const modalDate = document.getElementById('modal-date');
        const modalDescription = document.getElementById('modal-description');
        const modalPrevBtn = document.getElementById('slider-prev');
        const modalNextBtn = document.getElementById('slider-next');

        let currentImages = [];
        let currentSlideIndex = 0;

        function showSlide(index) {
            if (!slider) return;
            const slides = slider.querySelectorAll('img');
            if (slides.length === 0) return;
            slides.forEach(slide => slide.classList.remove('is-active'));
            if (slides[index]) slides[index].classList.add('is-active');
            
            const hasMultipleImages = slides.length > 1;
            if(modalPrevBtn) modalPrevBtn.style.display = hasMultipleImages ? 'block' : 'none';
            if(modalNextBtn) modalNextBtn.style.display = hasMultipleImages ? 'block' : 'none';
        }

        async function populateModal(itemId) {
            try {
                const response = await fetch(`gallery.php?action=get_gallery&id=${itemId}`);
                if (!response.ok) throw new Error(`Network response error. Status: ${response.status}`);
                const itemData = await response.json();
                if (!itemData || itemData.error) {
                    console.error('API Error:', itemData.error);
                    return;
                }

                currentImages = itemData.images || [];
                currentSlideIndex = 0;

                if(modalTitle) modalTitle.textContent = itemData.title;
                if(modalDescription) modalDescription.innerHTML = itemData.description ? itemData.description.replace(/\r\n/g, '<br>').replace(/\n/g, '<br>') : '';
                if(modalDate) {
                    modalDate.textContent = itemData.event_date;
                    modalDate.style.display = itemData.event_date ? 'block' : 'none';
                }
                if(slider) {
                    slider.innerHTML = '';
                    if (currentImages.length > 0) {
                        currentImages.forEach(src => {
                            const img = document.createElement('img');
                            img.src = src;
                            img.alt = itemData.title;
                            slider.appendChild(img);
                        });
                    } else {
                        slider.innerHTML = `<div class="w-full h-full flex items-center justify-center text-white/50">No images available.</div>`;
                    }
                }
                showSlide(currentSlideIndex);
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } catch (error) {
                console.error('Failed to populate modal:', error);
            }
        }

        if (allGalleryCards.length > 0) {
            allGalleryCards.forEach(card => {
                card.addEventListener('click', () => {
                    const galleryId = card.dataset.galleryId;
                    if (galleryId) populateModal(galleryId);
                });
            });
        }
        
        function hideModal() {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        if(closeModalBtn) closeModalBtn.addEventListener('click', hideModal);
        if(modal) modal.addEventListener('click', (e) => (e.target.id === 'gallery-modal') && hideModal());
        document.addEventListener('keydown', (e) => (e.key === 'Escape' && !modal.classList.contains('hidden')) && hideModal());

        if(modalNextBtn) modalNextBtn.addEventListener('click', () => { currentSlideIndex = (currentSlideIndex + 1) % currentImages.length; showSlide(currentSlideIndex); });
        if(modalPrevBtn) modalPrevBtn.addEventListener('click', () => { currentSlideIndex = (currentSlideIndex - 1 + currentImages.length) % currentImages.length; showSlide(currentSlideIndex); });
    });
    </script>
</body>
</html>
<?php
} // End of the `else` block for showing the public page.
?>
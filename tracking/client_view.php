<?php
include 'config.php'; // Your database connection

$shareable_id = isset($_GET['id']) ? $_GET['id'] : '';
$item_details = null;
$progress_history = [];
$latest_progress = "Awaiting Progress"; // Default status

if (!empty($shareable_id)) {
    // Fetch the main item details using the shareable ID
    $stmt = $conn->prepare("SELECT id, item_name, item_id, client FROM items WHERE shareable_id = ?");
    $stmt->bind_param("s", $shareable_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $item_details = $result->fetch_assoc();
        $item_internal_id = $item_details['id'];

        // Fetch the progress history for that item using the internal id
        $stmt_progress = $conn->prepare(
            "SELECT progress, description, date, time, image_path FROM progress 
             WHERE item_primary_id = ?
             ORDER BY date DESC, time DESC"
        );
        $stmt_progress->bind_param("i", $item_internal_id);
        $stmt_progress->execute();
        $progress_result = $stmt_progress->get_result();
        while ($row = $progress_result->fetch_assoc()) {
            $progress_history[] = $row;
        }
        $stmt_progress->close();

        // Get the latest progress status if history exists
        if (!empty($progress_history)) {
            $latest_progress = $progress_history[0]['progress'];
        }
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Progress Tracker - Legasi Futura</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        #main-header {
            position: fixed !important;
        }

        /* --- Styles for Client Tracking Timeline --- */
        .tracking-container { max-width: 800px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .tracking-header { border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 30px; }
        .tracking-header h2 { text-align: right; color: #555; font-weight: normal; margin: 0; }
        .item-info { margin-top: 15px; }
        .item-info p { margin: 5px 0; font-size: 1.1em; color: #333; }
        
        .timeline { position: relative; list-style: none; padding: 0; margin-top: 30px; }
        
        .timeline:before { 
            content: ''; 
            position: absolute; 
            top: 40px; 
            bottom: 0; 
            width: 2px; 
            background: #ddd; 
            left: 20px; /* Aligns with center of the 40px icon */
        }

        .timeline-item { position: relative; margin-bottom: 30px; padding-left: 70px; }
        .timeline-item:last-child { margin-bottom: 0; }
        
        .timeline-icon { 
            position: absolute; 
            left: 0px; 
            top: 0; 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            background: #fff; 
            border: 3px solid #f2a202; 
            z-index: 10;
            /* The 'position: relative' that caused the error has been REMOVED */
        }

        /* Upward pointing arrow on the icon */
        .timeline-icon::after {
            content: '';
            position: absolute;
            width: 0;
            height: 0;
            /* Position arrow on top of the icon */
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 4px; /* Space between arrow and circle */
            /* Create triangle shape */
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid #f2a202; /* Arrow color */
        }
        
        .timeline-item.delivered .timeline-icon { 
            background: #28a745; 
            border-color: #28a745; 
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.5em; 
        }
        .timeline-item.delivered .timeline-icon:before { content: '✔'; }

        /* Hide the arrow on the delivered icon */
        .timeline-item.delivered .timeline-icon::after {
            display: none;
        }
        
        .timeline-body {
            position: relative;
            top: -5px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #eee;
        }
        
        .timeline-body .time { font-size: 0.9em; color: #888; margin-bottom: 5px; }
        .timeline-body h4 { margin: 0 0 5px 0; font-size: 1.1em; }
        .timeline-body p { margin: 0; color: #666; font-size: 1em; }
        .not-found { text-align: center; color: #888; font-size: 1.2em; padding: 40px; }
    </style>
</head>
<body class="bg-white text-gray-800">

    <header id="main-header" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-black/30 backdrop-blur-sm">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="index.html" class="flex items-center space-x-2">
                    <img src="img/logo.png" alt="Legasi Futura Logo" class="h-10">
                </a>
                <nav class="hidden lg:flex items-center space-x-6">
                    <a href="index.html" class="text-white/90 uppercase text-sm font-medium tracking-wider transition-colors duration-300 hover:text-white">Home</a>
                    <div class="relative group">
                        <button class="flex items-center space-x-1 text-white/90 uppercase text-sm font-medium tracking-wider transition-colors duration-300 hover:text-white">
                            <span>Who We Are</span>
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-48 bg-black/80 backdrop-blur-sm rounded-md shadow-lg opacity-0 group-hover:opacity-100 transition-all duration-300 invisible group-hover:visible pt-2 pb-2">
                            <a href="who-we-are.html" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Overview</a>
                            <a href="vision-mission.html" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Vision & Mission</a>
                            <a href="policies.html" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Our Policies</a>
                            <a href="cert.html" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Certification</a>
                            <a href="track-record.html" class="block w-full text-left px-4 py-2 text-sm text-white/90 hover:bg-gray-700/50 hover:text-white">Track Record</a>
                        </div>
                    </div>
                    <a href="what-we-do.html" class="text-white/90 uppercase text-sm font-medium tracking-wider transition-colors duration-300 hover:text-white">What We Do</a>
                    <a href="contact-us.html" class="text-white/90 uppercase text-sm font-medium tracking-wider transition-colors duration-300 hover:text-white">Contact Us</a>
                    <a href="career-internship.html" class="text-white/90 uppercase text-sm font-medium tracking-wider transition-colors duration-300 hover:text-white">Career / Internship</a>
                    <button class="text-white/90 hover:text-white transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
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
            <a href="who-we-are.html" class="text-white/90 uppercase text-lg font-medium tracking-wider transition-colors duration-300 hover:text-white">Who We Are</a>
            <div class="-mt-6 pl-4">
                <a href="who-we-are.html" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Overview</a><br>
                <a href="vision-mission.html" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Vision & Mission</a><br>
                <a href="policies.html" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Our Policies</a><br>
                <a href="cert.html" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Certification</a><br>
                <a href="track-record.html" class="text-white/70 uppercase text-base font-medium tracking-wider transition-colors duration-300 hover:text-white">Track Record</a>
            </div>
            <a href="what-we-do.html" class="text-white/90 uppercase text-lg font-medium tracking-wider transition-colors duration-300 hover:text-white">What We Do</a>
            <a href="contact-us.html" class="text-white/90 uppercase text-lg font-medium tracking-wider transition-colors duration-300 hover:text-white">Contact Us</a>
            <a href="career-internship.html" class="text-white/90 uppercase text-lg font-medium tracking-wider transition-colors duration-300 hover:text-white">Career / Internship</a>
        </nav>
    </div>

    <div id="main-container">
        <main class="pt-32 pb-20">
            <div class="tracking-container">
                <?php if ($item_details): ?>
                    <div class="tracking-header">
                        <h2>Hello, <?php echo htmlspecialchars($item_details['client']); ?></h2>
                        <div class="item-info"> 
                            <p><strong>Item Name:</strong> <?php echo htmlspecialchars($item_details['item_name']); ?></p>
                            <p><strong>Item ID:</strong> <?php echo htmlspecialchars($item_details['item_id']); ?></p>
                            <p><strong>Current Status:</strong> <?php echo htmlspecialchars($latest_progress); ?></p>
                        </div>
                    </div>

                    <ul class="timeline">
                        <?php if (empty($progress_history)): ?>
                            <li class="timeline-item">
                                <div class="timeline-icon"></div>
                                <div class="timeline-body">
                                    <h4>Awaiting Progress</h4>
                                    <p>No tracking updates have been recorded for this item yet.</p>
                                </div>
                            </li>
                        <?php else: ?>
                            <?php foreach ($progress_history as $index => $progress): ?>
                                <li class="timeline-item <?php echo (strtolower($progress['progress']) == 'delivery to client') ? 'delivered' : ''; ?>">
                                    <div class="timeline-icon"></div>
                                    <div class="timeline-body">
                                        <div class="time">
                                            <?php echo date('d/m/Y', strtotime($progress['date'])) . ' ' . date('g:i a', strtotime($progress['time'])); ?>
                                        </div>
                                        <h4><?php echo htmlspecialchars($progress['progress']); ?></h4>
                                        <p><?php echo htmlspecialchars($progress['description']); ?></p>
                                        <?php if (!empty($progress['image_path'])): ?>
                                            <p style="margin-top: 10px;">
                                                <a href="#" class="text-blue-600 hover:underline see-image-trigger" data-img-src="<?php echo htmlspecialchars($progress['image_path']); ?>">See Image</a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                <?php else: ?>
                    <p class="not-found">Tracking ID not found. Please check the URL and try again.</p>
                <?php endif; ?>
            </div>
            </main>

        <footer class="bg-gray-800 text-white py-12">
            <div class="container mx-auto px-6">
                <div class="text-center md:text-left">
                    <div class="mb-6 md:mb-0">
                        <img src="img/logo.png" alt="Legasi Futura Logo" class="h-10 mx-auto md:mx-0">
                        <p class="text-sm text-gray-400 mt-4">1st Floor, Lot 2161 Block 5,</p>
                        <p class="text-sm text-gray-400">Jalan Saberkas Utama, Jalan Pujut-Lutong,</p>
                        <p class="text-sm text-gray-400">98000 Miri, Sarawak, Malaysia.</p>
                        <div class="flex space-x-4 mt-4 justify-center md:justify-start">
                            <a href="https://www.linkedin.com/company/legasifutura/" class="text-gray-400 hover:text-white transition-colors duration-300">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd"/></svg>
                            </a>
                            <a href="https://www.facebook.com/p/Legasi-Futura-100094370892508/" class="text-gray-400 hover:text-white transition-colors duration-300">
                               <svg class="w-6 h-6" fill="currentColor" viewBox="0 24" d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>
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

    <!-- Image Modal -->
    <div id="image-modal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[100] p-4">
        <div class="bg-white p-2 rounded-lg shadow-xl max-w-4xl max-h-[90vh] relative">
            <button id="close-image-modal" class="absolute -top-3 -right-3 bg-gray-700 text-white rounded-full p-2 z-10 hover:bg-black transition-colors duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <img id="modal-image" src="" class="max-w-full max-h-[85vh]" alt="Progress Image">
        </div>
    </div>


    <a href="https://wa.me/60138626042" class="float" target="_blank">
        <i class="fa fa-whatsapp my-float"></i>
    </a>
    <a id="back-to-top-btn" href="#" class="hidden fixed bottom-28 right-10 bg-blue-900 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition-all duration-300 z-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
    </a>

    <script>
        // --- Legasi Futura Template JS ---
        const burgerMenuBtn = document.getElementById('burger-menu');
        const mobileMenu = document.getElementById('mobile-menu');
        const mainHeader = document.getElementById('main-header');
        const backToTopBtn = document.getElementById('back-to-top-btn');

        burgerMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
        
        let lastScrollY = window.scrollY;
        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            if (currentScrollY > lastScrollY && currentScrollY > 150) {
                mainHeader.classList.add('-translate-y-full');
            } else {
                mainHeader.classList.remove('-translate-y-full');
            }
            if (currentScrollY > 50) {
                mainHeader.classList.add('bg-black/80');
                mainHeader.classList.remove('bg-black/30');
            } else {
                mainHeader.classList.add('bg-black/30');
                mainHeader.classList.remove('bg-black/80');
            }
            lastScrollY = currentScrollY <= 0 ? 0 : currentScrollY; 
            if (window.scrollY > 300) {
                backToTopBtn.classList.remove('hidden');
            } else {
                backToTopBtn.classList.add('hidden');
            }
        });

        backToTopBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // --- Image Modal Logic ---
        const imageModal = document.getElementById('image-modal');
        if (imageModal) {
            const modalImage = document.getElementById('modal-image');
            const closeImageModalBtn = document.getElementById('close-image-modal');
            const mainContainer = document.getElementById('main-container');

            const hideImageModal = () => {
                imageModal.classList.add('hidden');
                modalImage.src = ''; 
            };

            mainContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('see-image-trigger')) {
                    e.preventDefault();
                    const imgSrc = e.target.dataset.imgSrc;
                    if (imgSrc) {
                        modalImage.src = imgSrc;
                        imageModal.classList.remove('hidden');
                    }
                }
            });

            closeImageModalBtn.addEventListener('click', hideImageModal);

            imageModal.addEventListener('click', (e) => {
                if (e.target.id === 'image-modal') {
                    hideImageModal();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === "Escape" && !imageModal.classList.contains('hidden')) {
                    hideImageModal();
                }
            });
        }
    </script>

</body>
</html>
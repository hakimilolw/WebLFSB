<?php
include 'config.php'; // Your database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LFSB - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 25px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; }
        .btn-primary { background-color: #3b82f6; color: white; }
        .btn-primary:hover { background-color: #2563eb; }
        .btn-danger { background-color: #ef4444; color: white; }
        .btn-danger:hover { background-color: #dc2626; }
        .btn-secondary { background-color: #6b7280; color: white; }
        .btn-secondary:hover { background-color: #4b5563; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .progress-entry { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 10px; }
        #notification { position: fixed; top: 20px; right: 20px; background-color: #22c55e; color: white; padding: 15px; border-radius: 5px; z-index: 2000; opacity: 0; visibility: hidden; transition: all 0.5s; }
        #notification.show { opacity: 1; visibility: visible; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <header class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <h1 class="text-2xl font-bold text-gray-800">Admin Control Panel</h1>
            <p class="text-gray-600">Manage all item and progress details from here.</p>
        </div>
    </header>

    <main class="container mx-auto px-6 py-8">
        <div id="notification"></div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">All Items</h2>
                <button id="addNewItemBtn" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Add New Item</button>
            </div>
            <div id="itemsContainer" class="space-y-6">
                <!-- Items will be loaded here by JavaScript -->
            </div>
        </div>
    </main>

    <!-- Add Item Modal -->
    <div id="addItemModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 class="text-xl font-semibold mb-4">Add New Item</h3>
            <form id="addItemForm">
                <div class="form-group"><label for="itemName">Item Name</label><input type="text" id="itemName" required></div>
                <div class="form-group"><label for="itemId">Item ID</label><input type="text" id="itemId" required></div>
                <div class="form-group"><label for="client">Client</label><input type="text" id="client" required></div>
                <div class="form-group"><label for="eta">ETA</label><input type="text" id="eta" required></div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Item Details Modal -->
    <div id="editItemModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 class="text-xl font-semibold mb-4">Edit Item Details</h3>
            <form id="editItemForm">
                <input type="hidden" id="editItemPrimaryId">
                <div class="form-group"><label for="editItemName">Item Name</label><input type="text" id="editItemName" required></div>
                <div class="form-group"><label for="editItemIdNo">Item ID</label><input type="text" id="editItemIdNo" required></div>
                <div class="form-group"><label for="editClient">Client</label><input type="text" id="editClient" required></div>
                <div class="form-group"><label for="editEta">ETA</label><input type="text" id="editEta" required></div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Edit Progress Entry Modal -->
    <div id="editProgressModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 class="text-xl font-semibold mb-4">Edit Progress Entry</h3>
            <form id="editProgressForm">
                <input type="hidden" id="editProgressId">
                <div class="form-group">
                    <label for="editProgressStatus">Progress Status</label>
                    <select id="editProgressStatus" required>
                         <option value="PO Received">PO Received</option>
                        <option value="Submission GA Drawing to client">Submission GA Drawing to client</option>
                        <option value="Kick off meeting">Kick off meeting</option>
                        <option value="Procurement and purchasing of materials">Procurement and purchasing of materials</option>
                        <option value="Drawing approve by client">Drawing approval by client</option>
                        <option value="Fabrication start">Fabrication in progress</option>
                        <option value="Valve assembly, pre testing">Valve assembly, pre testing</option>
                        <option value="FAT in Progress">FAT in Progress</option>
                        <option value="FAT with TPI">FAT with TPI</option>
                        <option value="Painting and coating in progress">Painting and coating in progress</option>
                        <option value="Packing and delivery arrangement">Packing and delivery arrangement</option>
                        <option value="Arrival at location with customs clearance">Arrival at location with customs clearance</option>
                        <option value="SAT in progress">SAT in progress</option>
                        <option value="Delivery to client's yard">Delivery to client's yard</option>
                        <option value="Delivered">Delivered</option>
                    </select>
                </div>
                <div class="form-group"><label for="editProgressDescription">Description</label><textarea id="editProgressDescription" rows="3"></textarea></div>
                <div class="form-group"><label for="editProgressDate">Date</label><input type="date" id="editProgressDate" required></div>
                <div class="form-group"><label for="editProgressTime">Time</label><input type="time" id="editProgressTime" required></div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Add Progress Entry Modal -->
    <div id="addProgressModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 class="text-xl font-semibold mb-4">Add New Progress Update</h3>
            <form id="addProgressForm">
                <input type="hidden" id="addProgressItemPrimaryId">
                <div class="form-group">
                    <label for="addProgressStatus">Progress Status</label>
                    <select id="addProgressStatus" required>
                         <option value="Purchase Order (PO) received">Purchase Order (PO) received</option>
                        <option value="Submission GA Drawing to client">Submission General Arrangement Drawing (GAD) to client</option>
                        <option value="Awaiting drawing approval from client">Awaiting drawing approval from client</option>
                        <option value="Kick off meeting">Kick off meeting</option>
                        <option value="Procurement and purchasing of materials">Procurement and purchasing of materials</option>
                        <option value="Drawing approve by client">Drawing approval by client</option>
                        <option value="Fabrication start">Fabrication in progress</option>
                        <option value="Valve assembly, pre testing">Valve assembly, pre testing</option>
                        <option value="Factory Acceptance Test (FAT) with Third Party Inspector (TPI) in progress"> Factory Acceptance Test (FAT) with Third Party Inspector (TPI) in progress</option>
                        <option value="Painting and coating in progress">Painting and coating in progress</option>
                        <option value="Packing and delivery arrangement">Packing and delivery arrangement</option>
                        <option value="Arrival at location with customs clearance">Arrival at location with customs clearance</option>
                        <option value="Site Acceptance Test (SAT) in progress">Site Acceptance Test (SAT) in progress</option>
                        <option value="Delivery to client's yard">Delivery to client's yard</option>
                        <option value="Delivered">Delivered</option>
                    </select>
                </div>
                <div class="form-group"><label for="addProgressDescription">Description</label><textarea id="addProgressDescription" rows="3"></textarea></div>
                <div class="form-group"><label for="addProgressDate">Date</label><input type="date" id="addProgressDate" required></div>
                <div class="form-group"><label for="addProgressTime">Time</label><input type="time" id="addProgressTime" required></div>
                <div class="form-group"><label for="addProgressImage">Image (Optional)</label><input type="file" id="addProgressImage" name="progressImage" accept="image/*"></div>
                <button type="submit" class="btn btn-primary">Add Update</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const itemsContainer = document.getElementById('itemsContainer');
        const modals = document.querySelectorAll('.modal');
        const closeBtns = document.querySelectorAll('.modal .close');
        const notification = document.getElementById('notification');

        // --- Generic Modal Handling ---
        function closeAllModals() {
            modals.forEach(modal => modal.style.display = 'none');
        }
        closeBtns.forEach(btn => btn.onclick = closeAllModals);
        window.onclick = event => {
            if (event.target.classList.contains('modal')) {
                closeAllModals();
            }
        };

        function showNotification(message, isError = false) {
            notification.textContent = message;
            notification.className = 'show';
            notification.style.backgroundColor = isError ? '#ef4444' : '#22c55e';
            setTimeout(() => {
                notification.className = '';
            }, 3000);
        }

        // --- Fetch and Render All Data ---
        async function fetchAndRender() {
            try {
                const response = await fetch('api.php?path=items_with_full_progress');
                if (!response.ok) throw new Error('Failed to fetch data');
                const items = await response.json();
                
                itemsContainer.innerHTML = ''; // Clear existing content
                if (items.length === 0) {
                    itemsContainer.innerHTML = '<p>No items found. Add one to get started!</p>';
                    return;
                }

                items.forEach(item => {
                    const itemCard = document.createElement('div');
                    itemCard.className = 'bg-white border border-gray-200 rounded-lg shadow-sm p-4';
                    itemCard.setAttribute('data-item-id', item.id);

                    let progressHtml = '<p class="text-gray-500">No progress updates yet.</p>';
                    if (item.progress_history && item.progress_history.length > 0) {
                        progressHtml = item.progress_history.map(p => `
                            <div class="progress-entry flex justify-between items-center" data-progress-id="${p.progress_id}">
                                <div>
                                    <p class="font-semibold">${p.progress}</p>
                                    <p class="text-sm text-gray-600">${p.description || ''}</p>
                                    <p class="text-xs text-gray-500">${new Date(p.date + 'T' + p.time).toLocaleString('en-GB')}</p>
                                </div>
                                <div class="flex space-x-2">
                                    <button class="btn btn-secondary edit-progress-btn"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-danger delete-progress-btn"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        `).join('');
                    }

                    itemCard.innerHTML = `
                        <div class="border-b pb-3 mb-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-bold">${item.item_name}</h3>
                                    <p class="text-sm text-gray-600">ID: ${item.item_id} | Client: ${item.client} | ETA: ${item.eta}</p>
                                </div>
                                <div class="flex space-x-2">
                                    <button class="btn btn-secondary edit-item-btn"><i class="fas fa-pencil-alt mr-2"></i>Edit Item</button>
                                    <button class="btn btn-danger delete-item-btn"><i class="fas fa-trash-alt mr-2"></i>Delete Item</button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-2">Progress History</h4>
                            <div class="space-y-2">${progressHtml}</div>
                            <button class="btn btn-primary mt-3 add-progress-btn"><i class="fas fa-plus mr-2"></i>Add Progress</button>
                        </div>
                    `;
                    itemsContainer.appendChild(itemCard);
                });

            } catch (error) {
                console.error('Error fetching data:', error);
                showNotification('Could not load item data.', true);
            }
        }

        // --- Event Delegation for Buttons ---
        itemsContainer.addEventListener('click', async (e) => {
            const itemCard = e.target.closest('[data-item-id]');
            if (!itemCard) return;
            const primaryId = itemCard.getAttribute('data-item-id');

            // Edit Item Button
            if (e.target.closest('.edit-item-btn')) {
                const response = await fetch(`api.php?path=item/${primaryId}`);
                const item = await response.json();
                document.getElementById('editItemPrimaryId').value = item.id;
                document.getElementById('editItemName').value = item.item_name;
                document.getElementById('editItemIdNo').value = item.item_id;
                document.getElementById('editClient').value = item.client;
                document.getElementById('editEta').value = item.eta;
                document.getElementById('editItemModal').style.display = 'block';
            }

            // Delete Item Button
            if (e.target.closest('.delete-item-btn')) {
                if (confirm('Are you sure you want to delete this entire item and all its progress? This cannot be undone.')) {
                    const response = await fetch(`api.php?path=deleteItem/${primaryId}`, { method: 'DELETE' });
                    const result = await response.json();
                    showNotification(result.message);
                    fetchAndRender();
                }
            }

            // Add Progress Button
            if (e.target.closest('.add-progress-btn')) {
                document.getElementById('addProgressForm').reset();
                document.getElementById('addProgressItemPrimaryId').value = primaryId;
                document.getElementById('addProgressDate').valueAsDate = new Date();
                document.getElementById('addProgressTime').value = new Date().toTimeString().slice(0, 5);
                document.getElementById('addProgressModal').style.display = 'block';
            }

            const progressEntry = e.target.closest('[data-progress-id]');
            if(!progressEntry) return;
            const progressId = progressEntry.getAttribute('data-progress-id');

            // Edit Progress Button
            if (e.target.closest('.edit-progress-btn')) {
                const response = await fetch(`api.php?path=progress_entry/${progressId}`);
                const progress = await response.json();
                document.getElementById('editProgressId').value = progress.progress_id;
                document.getElementById('editProgressStatus').value = progress.progress;
                document.getElementById('editProgressDescription').value = progress.description;
                document.getElementById('editProgressDate').value = progress.date;
                document.getElementById('editProgressTime').value = progress.time;
                document.getElementById('editProgressModal').style.display = 'block';
            }

            // Delete Progress Button
            if (e.target.closest('.delete-progress-btn')) {
                if (confirm('Are you sure you want to delete this progress entry?')) {
                    const response = await fetch(`api.php?path=deleteProgressEntry/${progressId}`, { method: 'DELETE' });
                    const result = await response.json();
                    showNotification(result.message);
                    fetchAndRender();
                }
            }
        });

        // --- Form Submissions ---

        // Add New Item
        document.getElementById('addNewItemBtn').onclick = () => {
            document.getElementById('addItemForm').reset();
            document.getElementById('addItemModal').style.display = 'block';
        };
        document.getElementById('addItemForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const itemData = {
                itemName: document.getElementById('itemName').value,
                itemId: document.getElementById('itemId').value,
                client: document.getElementById('client').value,
                eta: document.getElementById('eta').value
            };
            const response = await fetch('api.php?path=addItem', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(itemData)
            });
            const result = await response.json();
            showNotification(result.message);
            closeAllModals();
            fetchAndRender();
        });
        
        // Edit Item Details
        document.getElementById('editItemForm').addEventListener('submit', async(e) => {
            e.preventDefault();
            const id = document.getElementById('editItemPrimaryId').value;
            const itemData = {
                itemName: document.getElementById('editItemName').value,
                itemId: document.getElementById('editItemIdNo').value,
                client: document.getElementById('editClient').value,
                eta: document.getElementById('editEta').value
            };
            const response = await fetch(`api.php?path=updateItemDetails/${id}`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(itemData)
            });
            const result = await response.json();
            showNotification(result.message);
            closeAllModals();
            fetchAndRender();
        });

        // Add Progress Entry
        document.getElementById('addProgressForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('addProgressItemPrimaryId').value;
            const formData = new FormData();
            formData.append('progress', document.getElementById('addProgressStatus').value);
            formData.append('description', document.getElementById('addProgressDescription').value);
            formData.append('date', document.getElementById('addProgressDate').value);
            formData.append('time', document.getElementById('addProgressTime').value);
            const imageInput = document.getElementById('addProgressImage');
            if (imageInput.files.length > 0) {
                formData.append('progressImage', imageInput.files[0]);
            }

            const response = await fetch(`api.php?path=addProgressToItem/${id}`, {
                method: 'POST', body: formData
            });
            const result = await response.json();
            showNotification(result.message);
            closeAllModals();
            fetchAndRender();
        });

        // Edit Progress Entry
        document.getElementById('editProgressForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('editProgressId').value;
            const data = {
                progress: document.getElementById('editProgressStatus').value,
                description: document.getElementById('editProgressDescription').value,
                date: document.getElementById('editProgressDate').value,
                time: document.getElementById('editProgressTime').value
            };
            const response = await fetch(`api.php?path=updateProgressEntry/${id}`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
            });
            const result = await response.json();
            showNotification(result.message);
            closeAllModals();
            fetchAndRender();
        });

        // Initial Load
        fetchAndRender();
    });
    </script>
</body>
</html>
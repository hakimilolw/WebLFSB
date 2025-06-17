// Replace the entire content of your script.js with this

document.addEventListener('DOMContentLoaded', () => {
    // --- START: PASTE YOUR FIREBASE CONFIGURATION HERE ---
    const firebaseConfig = {
        apiKey: "AIzaSyBoR4lAyQEFow5BTgbQ9Q-6Uyd_JE9G4RE",
        authDomain: "submission-69979.firebaseapp.com",
        projectId: "submission-69979",
        storageBucket: "submission-69979.firebasestorage.app",
        messagingSenderId: "407010052449",
        appId: "1:407010052449:web:99c4772d7a2328f23c1d20"
    };
    // --- END: PASTE YOUR FIREBASE CONFIGURATION HERE ---

    // Initialize Firebase
    firebase.initializeApp(firebaseConfig);
    const db = firebase.firestore(); // Get a reference to the Firestore database

    const submissionForm = document.getElementById('submissionForm');
    const dashboard = document.getElementById('dashboard');

    // REAL-TIME LISTENER: This is the magic of Firebase!
    // This function will run automatically every time the data changes in the database.
    db.collection("submissions").orderBy("deadlineDate", "asc").onSnapshot(querySnapshot => {
        const submissions = [];
        querySnapshot.forEach(doc => {
            submissions.push({ id: doc.id, ...doc.data() });
        });
        renderDashboard(submissions); // Re-render the dashboard with fresh data
    });

    // Function to render the dashboard
    const renderDashboard = (submissions) => {
        dashboard.innerHTML = '';
        if (submissions.length === 0) {
            dashboard.innerHTML = '<p>No submissions yet.</p>';
            return;
        }

        const groupedByDay = submissions.reduce((acc, submission) => {
            const date = new Date(submission.deadlineDate).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            if (!acc[date]) {
                acc[date] = [];
            }
            acc[date].push(submission);
            return acc;
        }, {});

        for (const day in groupedByDay) {
            const dayColumn = document.createElement('div');
            dayColumn.classList.add('day-column');
            const dayHeader = document.createElement('div');
            dayHeader.classList.add('day-header');
            dayHeader.textContent = day;
            dayColumn.appendChild(dayHeader);

            groupedByDay[day].forEach(submission => {
                const card = document.createElement('div');
                card.classList.add('submission-card');
                const deadline = new Date(`<span class="math-inline">\{submission\.deadlineDate\}T</span>{submission.deadlineTime}`);
                card.innerHTML = `
                    <p class="file-name">${submission.fileName}</p>
                    <p><strong>In Charge:</strong> ${submission.inCharge}</p>
                    <p><strong>Deadline:</strong> ${deadline.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</p>
                    <p><strong>Description:</strong> ${submission.description}</p>
                `;
                dayColumn.appendChild(card);
            });
            dashboard.appendChild(dayColumn);
        }
    };

    // Handle form submission
    submissionForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const newSubmission = {
            fileName: document.getElementById('fileName').value,
            deadlineDate: document.getElementById('deadlineDate').value,
            deadlineTime: document.getElementById('deadlineTime').value,
            inCharge: document.getElementById('inCharge').value,
            description: document.getElementById('description').value,
            createdAt: firebase.firestore.FieldValue.serverTimestamp() // Adds a server timestamp
        };

        // Add a new document to the "submissions" collection in Firestore
        db.collection("submissions").add(newSubmission)
            .then(() => {
                console.log("Document successfully written!");
                submissionForm.reset();
            })
            .catch((error) => {
                console.error("Error writing document: ", error);
                alert("Failed to save submission.");
            });
    });
});
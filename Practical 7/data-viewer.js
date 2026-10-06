// Data Viewer Logic for Practical 6

let allData = [];
let filteredData = [];
let currentPage = 1;
const itemsPerPage = 6;

const dataSource = document.getElementById('dataSource');
const searchInput = document.getElementById('searchInput');
const sortSelect = document.getElementById('sortSelect');
const dataContainer = document.getElementById('dataContainer');
const statusMessage = document.getElementById('statusMessage');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const pageIndicator = document.getElementById('pageIndicator');

// Theme logic
const themeBtn = document.getElementById("themeBtn");
if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");
    if(themeBtn) themeBtn.textContent = "☀ Light";
}
if(themeBtn) {
    themeBtn.addEventListener("click", () => {
        document.body.classList.toggle("dark-mode");
        if (document.body.classList.contains("dark-mode")) {
            localStorage.setItem("theme", "dark");
            themeBtn.textContent = "☀ Light";
        } else {
            localStorage.setItem("theme", "light");
            themeBtn.textContent = "🌙 Dark";
        }
    });
}

// Fetch Data Using Fetch API
async function loadData() {
    statusMessage.textContent = "Loading data...";
    dataContainer.innerHTML = "";
    
    try {
        const response = await fetch(dataSource.value);
        if (!response.ok) throw new Error("Failed to load data.");
        const data = await response.json();
        
        allData = data;
        applyFilters(); // will handle search, sort, and render
        statusMessage.textContent = "";
    } catch (error) {
        statusMessage.textContent = "Error: " + error.message;
        statusMessage.style.color = "red";
    }
}

// Search, Sort, Filter logic using Array methods
function applyFilters() {
    let temp = [...allData];
    
    // 1. Search Logic using Array.filter()
    const q = searchInput.value.toLowerCase();
    if (q) {
        temp = temp.filter(item => {
            // Check all string properties for a match
            return Object.values(item).some(val => 
                String(val).toLowerCase().includes(q)
            );
        });
    }

    // 2. Sort Logic using Array.sort()
    const sortVal = sortSelect.value;
    if (sortVal) {
        temp.sort((a, b) => {
            // Determine the main key to sort by depending on the dataset
            const keyA = String(a.title || a.name || a.question);
            const keyB = String(b.title || b.name || b.question);
            
            if (sortVal === 'asc') return keyA.localeCompare(keyB);
            return keyB.localeCompare(keyA);
        });
    }

    filteredData = temp;
    currentPage = 1; // Reset to page 1 whenever filters change
    renderData();
}

// Render dynamic HTML elements based on JSON format
function renderData() {
    dataContainer.innerHTML = "";
    
    // Pagination logic using Array.slice()
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const paginated = filteredData.slice(start, end);
    
    if (paginated.length === 0) {
        dataContainer.innerHTML = "<p>No data found matching your criteria.</p>";
    } else {
        paginated.forEach(item => {
            const card = document.createElement('div');
            card.className = 'card-item';
            
            if (item.title) {
                // Render Event format
                card.innerHTML = `<h3>${item.title}</h3>
                                  <p>Date: ${item.date}</p>
                                  <p>Category: ${item.category}</p>
                                  <p>Status: <strong>${item.status}</strong></p>`;
            } else if (item.name) {
                // Render Student format
                card.innerHTML = `<h3>${item.name}</h3>
                                  <p>Course: ${item.course}</p>
                                  <p>Year: ${item.year}</p>
                                  <p>GPA: ${item.gpa}</p>`;
            } else if (item.question) {
                // Render FAQ format
                card.innerHTML = `<h3>${item.question}</h3>
                                  <p>${item.answer}</p>
                                  <p><small>Category: ${item.category}</small></p>`;
            }
            
            dataContainer.appendChild(card);
        });
    }
    
    // Update Pagination UI Controls
    const totalPages = Math.ceil(filteredData.length / itemsPerPage) || 1;
    pageIndicator.textContent = `Page ${currentPage} of ${totalPages}`;
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = end >= filteredData.length;
}

// Event Listeners for Filters
dataSource.addEventListener('change', loadData);
searchInput.addEventListener('keyup', applyFilters);
sortSelect.addEventListener('change', applyFilters);

// Event Listeners for Pagination
prevBtn.addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        renderData();
    }
});

nextBtn.addEventListener('click', () => {
    if ((currentPage * itemsPerPage) < filteredData.length) {
        currentPage++;
        renderData();
    }
});

// Initial Load on Page Startup
loadData();


/* =====================================
   Dependent Dropdowns (Intermediate)
===================================== */
const locations = {
    India: {
        Gujarat: ["Ahmedabad", "Surat", "Vadodara", "Rajkot"],
        Maharashtra: ["Mumbai", "Pune", "Nagpur", "Nashik"]
    },
    USA: {
        California: ["Los Angeles", "San Francisco", "San Diego"],
        Texas: ["Houston", "Austin", "Dallas"]
    }
};

const countrySelect = document.getElementById('countrySelect');
const stateSelect = document.getElementById('stateSelect');
const citySelect = document.getElementById('citySelect');

// When Country changes -> Update State Dropdown
countrySelect.addEventListener('change', function() {
    const country = this.value;
    stateSelect.innerHTML = '<option value="">Select State</option>';
    citySelect.innerHTML = '<option value="">Select City</option>';
    
    if (country) {
        stateSelect.disabled = false;
        // Populate states dynamically based on selected country
        Object.keys(locations[country]).forEach(state => {
            stateSelect.innerHTML += `<option value="${state}">${state}</option>`;
        });
    } else {
        stateSelect.disabled = true;
        citySelect.disabled = true;
    }
});

// When State changes -> Update City Dropdown
stateSelect.addEventListener('change', function() {
    const country = countrySelect.value;
    const state = this.value;
    citySelect.innerHTML = '<option value="">Select City</option>';
    
    if (state) {
        citySelect.disabled = false;
        // Populate cities dynamically based on selected country and state
        locations[country][state].forEach(city => {
            citySelect.innerHTML += `<option value="${city}">${city}</option>`;
        });
    } else {
        citySelect.disabled = true;
    }
});

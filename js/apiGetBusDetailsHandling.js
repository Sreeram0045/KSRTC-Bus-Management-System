const form = document.getElementById('busDetailInput');

form.addEventListener('submit', fetchdata);

async function fetchdata(event) {
    event.preventDefault(); // This prevents the default form submission
    console.log("Form submitted");
    const formData = new FormData(form);
    const formDataObj = Object.fromEntries(formData.entries());
    formDataObj.submit_start_and_end = true;
    try {
        let responseFetch = await fetch("./api/apiGetBusDetails.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(formDataObj)
        });

        if (!responseFetch.ok) {
            console.error("HTTP Error:", responseFetch.status, responseFetch.statusText);
            throw new Error(`HTTP error! status: ${responseFetch.status}`);
        }

        const data = await responseFetch.json();

        console.log(data);

        const errorContainer = document.getElementById("error-container");
        const resultContainer = document.getElementById("result-container");

        if (data.length === 0 || (data.length > 0 && data[0].hasOwnProperty('no_services'))) {
            // No buses available or error response
            errorContainer.style.display = 'block';
            resultContainer.style.display = 'none';
            errorContainer.innerHTML = "<h1>OOPS!! Sorry no data found</h1>";
            errorContainer.scrollIntoView({ behavior: 'smooth' });
        } else {
            // Buses available
            errorContainer.style.display = 'none';
            resultContainer.style.display = 'block';
            populateTable(data);
            resultContainer.scrollIntoView({ behavior: 'smooth' });
        }

    } catch (error) {
        console.error(error);
        const errorContainer = document.getElementById("error-container");
        errorContainer.style.display = 'block';
        errorContainer.innerHTML = "<h1>An error occurred. Please try again.</h1>";
        errorContainer.scrollIntoView({ behavior: 'smooth' });
    }
}


function populateTable(data) {
    resultSection = document.getElementById("result-container");
    resultSection.style.display = "block";
    tableBody = document.getElementById("result-table-body");
    tableBody.innerHTML = '';

    data.forEach(element => {
        const row = document.createElement('tr');
        const cell1 = document.createElement('td');
        const cell2 = document.createElement('td');
        const cell3 = document.createElement('td');
        const cell4 = document.createElement('td');
        const cell5 = document.createElement('td');
        const cell6 = document.createElement('td');
        const cell7 = document.createElement('td');
        const cell8 = document.createElement('td');
        const cell9 = document.createElement('td');
        const moreDetailsForm = document.createElement('form');
        moreDetailsForm.action = './pages/details.php';
        moreDetailsForm.method = 'POST';
        const hiddenInputInsideForm = document.createElement('input');
        hiddenInputInsideForm.type = 'hidden';
        hiddenInputInsideForm.name = 'bus_id';
        hiddenInputInsideForm.value = element.bus_id;
        moreDetailsButton = document.createElement('button');
        moreDetailsButton.setAttribute('type', 'submit');
        moreDetailsButton.className = 'details-button';
        moreDetailsButton.name = 'redirect_submit';
        moreDetailsButton.innerText = 'Details';

        moreDetailsForm.appendChild(hiddenInputInsideForm);
        moreDetailsForm.appendChild(moreDetailsButton);

        cell9.appendChild(moreDetailsForm);

        cell1.setAttribute("data-title", "Bus ID");
        cell2.setAttribute("data-title", "Service");
        cell3.setAttribute("data-title", "Pickup Point");
        cell4.setAttribute("data-title", "Scheduled Reaching Time");
        cell5.setAttribute("data-title", "Drop-Off Point");
        cell6.setAttribute("data-title", "Scheduled Dropping Time");
        cell7.setAttribute("data-title", "Current Status");
        cell8.setAttribute("data-title", "Current Delay");

        cell1.innerText = element.bus_id;
        cell2.innerText = element.service_name;
        cell3.innerText = element.start_point;
        cell4.innerText = element.start_scheduled_time;
        cell5.innerText = element.end_point;
        cell6.innerText = element.end_scheduled_time;
        cell7.innerText = element.status;
        cell8.innerText = element.delay;

        row.appendChild(cell1);
        row.appendChild(cell2);
        row.appendChild(cell3);
        row.appendChild(cell4);
        row.appendChild(cell5);
        row.appendChild(cell6);
        row.appendChild(cell7);
        row.appendChild(cell8);
        row.appendChild(cell9);

        tableBody.appendChild(row);
        resultSection.scrollIntoView({
            behavior: 'smooth'
        });
    });
}
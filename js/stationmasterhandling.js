// functions to handle the pop-up boxes

function openPopup(type, busId) {
    // Use the busId to load specific data for the pop-up
    if (type === 'delay') {
        document.getElementById('busIdForDelay').innerText = busId;
        document.getElementById('hidden_bus_id_for_delay').value = busId;
    } else if (type === 'status') {
        document.getElementById('busIdForStatus').innerText = busId;
        document.getElementById('hidden_bus_id_for_status').value = busId;
    }

    // Show overlay and corresponding pop-up
    document.getElementById('overlay').classList.add('active');
    if (type === 'delay') {
        document.getElementById('delayPopup').classList.add('active');
    } else if (type === 'status') {
        document.getElementById('statusPopup').classList.add('active');
    }
}

function closePopup() {
    document.getElementById('overlay').classList.remove('active');
    document.getElementById('delayPopup').classList.remove('active');
    document.getElementById('statusPopup').classList.remove('active');
}

// functions to handle the backend api
const myHeaders = new Headers();
myHeaders.append("Content-Type", "application/json");
const delayForm = document.getElementById('delayForm');
const statusForm = document.getElementById('statusForm');

// To validate the input in delay form
const delayInput = document.getElementById('delay');
const delayBusId = document.getElementById('hidden_bus_id_for_delay');
const delayRegex = /^\d+$/;
function checkInputDelay() {
    if (delayInput.value.trim() === "") {
        delayInput.value = "";
        delayInput.placeholder = "Input can't be blank";
        delayInput.setAttribute("--placeholder-color", "red");
        return false;
    } else if (!delayRegex.test(delayInput.value.trim())) {
        delayInput.value = "";
        delayInput.placeholder = "Input can only be number";
        delayInput.setAttribute("--placeholder-color", "red");
        return false;
    }
    return true;
}
function errorInputDelay(inputString) {
    delayInput.value = "";
    delayInput.placeholder = inputString;
    delayInput.setAttribute("--placeholder-color", "red");
}
// sendDelay
delayForm.addEventListener('submit', validateAndSendDelay);

async function validateAndSendDelay(event) {
    event.preventDefault();
    console.log("Delay Form Prevented from submitting");
    if (!checkInputDelay()) {
        console.log("Form data is invalid");
        return false;
    }
    console.log("Form data is valid");
    // providing data to api
    try {
        let delayResponse = await fetch('../api/apiStationMasterDelay.php', {
            method: 'POST',
            headers: myHeaders,
            body: JSON.stringify({ 'busId': delayBusId.value, 'delay': delayInput.value })
        });
        if (!delayResponse.ok) {
            console.error("HTTP Error:", delayResponse.status, delayResponse.statusText);
            throw new Error(`HTTP error! status: ${delayResponse.status}`);
        }
        const delayResponseData = await delayResponse.json();
        if (delayResponseData['invalid'] === 'true') {
            console.log("Invalid Input/ blank");
            errorInputDelay("Input can't be blank");
        } else {
            if (delayResponseData['valid'] === 'true') {
                console.log("Delay Updated Succesfully");
                closePopup();
            } else {
                console.log("Query Error");
                errorInputDelay("Query Error");
            }
        }
    } catch (error) {
        console.log(error);
    }
}

// getting elements for status
const statusBusId = document.getElementById('hidden_bus_id_for_status');
const statusValue = document.getElementById('status_of_bus');

// getting error container
const errorContainerForStatus = document.getElementById('error-result-status-popup');
function errorStatus() {
    errorContainerForStatus.innerText = "Fucntion/Query Failed";
    errorContainerForStatus.display = "block";
}
// sendStatus
statusForm.addEventListener('submit', validateAndSendStatus);

async function validateAndSendStatus(event) {
    event.preventDefault();
    console.log("Status Form Prevented from submitting");
    console.log(statusValue.value);
    try {
        let statusResponse = await fetch('../api/apiStationMasterStatus.php', {
            method: 'POST',
            headers: myHeaders,
            body: JSON.stringify({ 'busId': statusBusId.value, 'status': statusValue.value })
        });
        if (!statusResponse.ok) {
            console.error("HTTP Error:", statusResponse.status, statusResponse.statusText);
            throw new Error(`HTTP error! status: ${statusResponse.status}`);
        }
        const statusResponseData = await statusResponse.json();
        if (statusResponseData['success'] === 'true') {
            console.log("Status Updated");
            closePopup();
        } else {
            console.log("Status Updation Failed/Query Failed");
            errorStatus();
        }
    } catch (error) {
        console.log(error);
    }
}
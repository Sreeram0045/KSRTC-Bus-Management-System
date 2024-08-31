const username = document.getElementById('login_username');
const password = document.getElementById('login_password');
const username_regexp = new RegExp("^[A-Za-z]\w{5,29}$");
const password_regexp = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
// header for fetch api request
const myHeaders = new Headers();
myHeaders.append("Content-Type", "application/json");


// function to check username
function checkUsername() {
    console.log(username.value);
    if (username.value.trim() === '') {
        username.value = "";
        username.placeholder = "Username shouldn't be blank";
        username.style.setProperty("--placeholder-color", "red");
        return false;
    } else if (!username_regexp.test(username.value.trim())) {
        username.value = "";
        console.log("Checked and Failed username");
        username.placeholder = "Enter a valid Username";
        username.style.setProperty("--placeholder-color", "red");
        return false;
    }
    return true;
}

// function to check password
function checkPassword() {
    console.log("Call to check password");
    console.log(password.value);
    if (password.value.trim() === '') {
        password.value = "";
        password.placeholder = "Password shouldn't be blank";
        password.style.setProperty("--placeholder-color", "red");
        return false;
    } else if (!password_regexp.test(password.value.trim())) {
        console.log("Checked and Failed password");
        password.value = "";
        password.placeholder = "Enter a valid Password";
        password.style.setProperty("--placeholder-color", "red");
        return false;
    }
    return true;
}

// function which returns the result invalid credentials when called
function inValidCredentials() {
    username.value = "";
    password.value = "";
    username.placeholder = "Invalid Credentials";
    password.placeholder = "Invalid Credentials";
    username.style.setProperty("--placeholder-color", "red");
    password.style.setProperty("--placeholder-color", "red");
}

const form = document.getElementById('login_form');

form.addEventListener('submit', validateAndSend)

async function validateAndSend(event) {
    event.preventDefault();
    console.log("Form Prevented from submitting");
    // const formData = new FormData(form);
    // const formDataObj = Object.fromEntries(formData.entries());
    // console.log(formDataObj);
    const isUserNameValid = true;
    const isPassWordValid = true;
    if (!(isUserNameValid && isPassWordValid)) {
        console.log("Form Validated and data is incorrect.");
        return false;
    }
    console.log("Form Validated and Data is correct.");
    console.log(username.value);
    console.log(password.value);
    try {
        let responseFetch = await fetch('../api/apiLoginProcess.php', {
            method: 'POST',
            headers: myHeaders,
            body: JSON.stringify({ 'username': username.value, 'password': password.value })
        });

        if (!responseFetch.ok) {
            console.error("HTTP Error:", responseFetch.status, responseFetch.statusText);
            throw new Error(`HTTP error! status: ${responseFetch.status}`);
        }
        const data = await responseFetch.json();
        console.log(data);
        if (data['success'] === 'true') {
            if (data['role'] === 'admin') {
                window.location.href = './admin.php';
            } else {
                window.location.href = './stationmaster.php';
            }
        } else {
            inValidCredentials();
        }
    } catch (error) {
        console.log(error);
    }
}
const successMessage = document.getElementById('success-box');
const failureMessage = document.getElementById('error-box');
const overlayLayer = document.getElementById('overlay');
function addOverlaySuccess() {
    successMessage.classList.add('active');
    overlayLayer.classList.add('active');
    setTimeout(() => {
        successMessage.classList.remove('active');
        overlayLayer.classList.remove('active');
    }, 2000);
}

function addOverlayFailure(message = 'Oh No, Something went Wrong') {
    if (message !== 'Oh No, Something went Wrong') {
        const failureContent = document.getElementById('message-info-failure');
        failureContent.textContent = "";
        failureContent.textContent = message;
    }
    failureMessage.classList.add('active');
    overlayLayer.classList.add('active');
    setTimeout(() => {
        failureMessage.classList.remove('active');
        overlayLayer.classList.remove('active');
    }, 2000);
}

function removeService(busId) {
    if (confirm('Are you sure you want to delete this bus service?')) {
        fetch('../pages/admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&busId=${busId}`
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    addOverlaySuccess();
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);
                } else {
                    addOverlayFailure("Sorry an error occured while deleting the bus service");
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                addOverlayFailure('An error occurred while deleting the bus service');
                // alert('An error occurred while deleting the bus service');
            });
    }
}

function editService(busId) {
    fetch("../pages/admin.php", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=edit&busId=${busId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit-form-container').innerHTML = data.html;
            } else {
                addOverlayFailure('Failed to load edit form');
                // alert('Failed to load edit form');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            addOverlayFailure('An error occurred while loading the edit form');
            // alert('An error occurred while loading the edit form');
        });
}

function manageStationMaster() {
    fetch("../pages/admin.php", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=manage_station_master`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit-form-container').innerHTML = data.html;
            } else {
                addOverlayFailure('Failed to load edit form');
                // alert('Failed to load edit form');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            addOverlayFailure('An error occurred while loading the edit form');
            // alert('An error occurred while loading the edit form');
        });
}

function manageNewService() {
    fetch("../pages/admin.php", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=input_new_bus`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit-form-container').innerHTML = data.html;
                // Initialize the form after it's added to the DOM
                initializeNewBusForm();
            } else {
                addOverlayFailure('Failed to load new bus form');
                // alert('Failed to load new bus form');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            addOverlayFailure('An error occurred while loading the new bus form');
            // alert('An error occurred while loading the new bus form');
        });
}



function editServiceInput(event) {
    event.preventDefault();
    const editForm = document.getElementById('edit-bus-form');
    const formData = new FormData(editForm);
    const busId = editForm.getAttribute('data-bus-id');

    const formDataObj = {
        busId: busId,
        startTime: formData.get('start_time'),
        endTime: formData.get('end_time'),
        days: formData.getAll('days[]'),
        action: 'update'
    };

    fetch("../pages/admin.php", {
        method: 'POST',
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(formDataObj)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addOverlaySuccess();
                // alert('Bus schedule updated successfully!');
                setTimeout(() => {
                    window.location.reload();
                }, 2500);
            } else {
                console.log(data.message);
                addOverlayFailure('Error updating bus schedule, Sorry for the Inconvenience');
                // alert('Error updating bus schedule: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error: ', error);
            addOverlayFailure('An error occurred while updating the bus schedule');
            // alert('An error occurred while updating the bus schedule');
        });
}

function validatePassword(password) {
    if (!password || password.trim() === '') {
        alert('Password cannot be empty');
        return false;
    }

    const hasLetters = /[a-zA-Z]/.test(password);
    const hasNumbers = /[0-9]/.test(password);

    if (!hasLetters || !hasNumbers) {
        alert('Password must contain both letters and numbers');
        return false;
    }

    if (password.length < 8) {
        alert('Password must be at least 8 characters long');
        return false;
    }

    return true;
}

function stationMasterManageInputReturn(event) {
    event.preventDefault();

    const stationMasterForm = document.getElementById('station-master-card');
    const formData = new FormData(stationMasterForm);

    const username = formData.get('value_of_username');
    const newPassword = formData.get('stationmaster_new_password');

    if (!validatePassword(newPassword)) {
        return;
    }

    const formDataObj = {
        username: username,
        new_password: newPassword,
        action: 'update_station_master_password'
    };

    fetch("../pages/admin.php", {
        method: 'POST',
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(formDataObj)
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                addOverlaySuccess();
                alert(`Password updated successfully for ${data.username}`);
                setTimeout(() => {
                    window.location.reload();
                }, 2500);
            } else {
                addOverlayFailure(`Error updating ${data.username}'s password`);
                console.log(`Error updating ${data.username}'s password: ${data.message}`);
                // alert(`Error updating ${data.username}'s password: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            addOverlayFailure('An error occurred while updating the password of station master');
            // alert('An error occurred while updating the password of station master');
        });
}

document.addEventListener('DOMContentLoaded', () => {
    // Add event listener for the edit form when it's added to the DOM
    document.body.addEventListener('submit', (event) => {
        if (event.target.id === 'edit-bus-form') {
            editServiceInput(event);
        }
        if (event.target.id === 'station-master-card') {
            stationMasterManageInputReturn(event);
        }
    });
});

// ... (previous code remains the same until initializeNewBusForm function)

function initializeNewBusForm() {
    const serviceType = document.getElementById('service-type');
    const stopsContainer = document.getElementById('stops-container');
    const addStopButton = document.getElementById('add-stop');
    const stopsList = document.getElementById('stops-list');
    const newBusForm = document.getElementById('new-bus-input');

    // Initially hide stops container
    stopsContainer.style.display = 'none';

    // Add event listener for service type change
    serviceType.addEventListener('change', function () {
        if (this.value === '1') { // Swift
            stopsContainer.style.display = 'none';
        } else {
            stopsContainer.style.display = 'block';
        }
    });

    // // Function to validate bus ID format
    // function validateBusId(busId, serviceType) {
    //     const busIdNum = parseInt(busId);
    //     switch (serviceType) {
    //         case '1': // Swift
    //             return busId.startsWith('1000');
    //         case '2': // Garuda
    //             return busId.startsWith('2000');
    //         case '3': // Minnal
    //             return busId.startsWith('3000');
    //         default:
    //             return false;
    //     }
    // }

    // Function to add a new stop
    function addStop() {
        const stopDiv = document.createElement('div');
        stopDiv.className = 'stop-item';

        const stopLabel = document.createElement('label');
        stopLabel.textContent = 'Stop: ';

        const stopSelect = document.createElement('select');
        stopSelect.name = 'stops[]';

        const stations = [
            'Thiruvananthapuram (Trivandrum)', 'Kollam', 'Pathanamthitta',
            'Alappuzha (Alleppey)', 'Kottayam', 'Idukki', 'Ernakulam (Kochi)',
            'Thrissur', 'Palakkad', 'Malappuram', 'Kozhikode', 'Wayanad',
            'Kannur', 'Kasaragod'
        ];

        stations.forEach((station, index) => {
            const option = document.createElement('option');
            option.value = index + 1;
            option.textContent = station;
            stopSelect.appendChild(option);
        });

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.textContent = 'Remove';
        removeButton.className = 'remove-stop-btn';
        removeButton.onclick = function () {
            stopDiv.remove();
        };

        stopDiv.appendChild(stopLabel);
        stopDiv.appendChild(stopSelect);
        stopDiv.appendChild(removeButton);
        stopsList.appendChild(stopDiv);
    }

    // Add click event listener for add stop button
    if (addStopButton) {
        addStopButton.addEventListener('click', function (e) {
            e.preventDefault();
            addStop();
        });
    }

    // Add form submission handler
    if (newBusForm) {
        newBusForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const serviceTypeValue = formData.get('service-id');
            const busId = parseInt(formData.get('bus_id_input')); // Parse to integer
            if (isNaN(busId)) {
                alert("Invalid Bus ID. Please enter a number.");
                return;
            }

            // Validate selected days
            const selectedDays = Array.from(document.querySelectorAll('input[name="check"]:checked'))
                .map(checkbox => checkbox.value);

            if (selectedDays.length === 0) {
                alert('Please select at least one day of operation');
                return;
            }

            // Validate times
            const startTime = formData.get('edited_start_time');
            const endTime = formData.get('edited_end_time');

            if (!startTime || !endTime) {
                alert('Please select both start and end times');
                return;
            }

            // Get stops if service is not Swift
            let stops = [];
            if (serviceTypeValue !== '1') {
                stops = Array.from(document.querySelectorAll('select[name="stops[]"]'))
                    .map(select => select.value);

                if (serviceTypeValue === '2' && stops.length !== 4) {
                    alert('Garuda service must have exactly 4 stops');
                    return;
                }
                if (serviceTypeValue === '3' && stops.length === 0) {
                    alert('Minnal service must have at least one stop');
                    return;
                }
            }

            // Create the request object
            const requestData = {
                action: 'insert_new_bus',
                busId: busId,
                serviceType: serviceTypeValue,
                startPoint: formData.get('start_point'),
                endPoint: formData.get('end_point'),
                startTime: startTime,
                endTime: endTime,
                operatingDays: selectedDays,
                stops: stops
            };

            // Submit the form data
            fetch('../pages/admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        addOverlaySuccess();
                        // alert('Bus service added successfully!');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2500);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.log('Error:', error);
                    addOverlayFailure('An error occurred while adding the bus service');
                    // alert('An error occurred while adding the bus service');
                });
        });
    }
}

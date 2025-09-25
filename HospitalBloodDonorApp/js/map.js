let map;
let userMarker;
const geocoder = new google.maps.Geocoder();

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: 0, lng: 0 },
        zoom: 2,
    });

    // Try to get user's current location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userPos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                // Center map on user's location
                map.setCenter(userPos);
                map.setZoom(12);

                // Add marker for user's location
                userMarker = new google.maps.Marker({
                    position: userPos,
                    map: map,
                    title: "Your Location",
                    icon: {
                        url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                    }
                });

                // Load donors near user's location
                loadDonors(userPos.lat, userPos.lng);
            },
            (error) => {
                console.error("Geolocation error:", error);
                // Default to some location if geolocation fails
                const defaultPos = { lat: 20.5937, lng: 78.9629 }; // India coordinates
                map.setCenter(defaultPos);
                loadDonors();
            }
        );
    } else {
        console.error("Geolocation is not supported by this browser.");
        const defaultPos = { lat: 20.5937, lng: 78.9629 };
        map.setCenter(defaultPos);
        loadDonors();
    }
}

// Simple dot symbol for hospital location
const hospitalIcon = {
    path: google.maps.SymbolPath.CIRCLE,
    fillColor: '#FF0000',
    fillOpacity: 1,
    strokeColor: '#FFFFFF',
    strokeWeight: 2,
    scale: 10
};

function loadDonors(userLat = null, userLng = null) {
    let url = 'get_donors.php';
    const bloodGroup = document.getElementById('blood-group').value;
    const radius = document.getElementById('radius-select').value;
    const hospitalAddress = document.getElementById('hospital-address').value;
    
    url += `?blood_group=${bloodGroup}`;

    // Clear existing hospital marker if any
    if (window.hospitalMarker) {
        window.hospitalMarker.setMap(null);
    }

    // Geocode hospital address if provided
    if (hospitalAddress) {
        geocoder.geocode({ address: hospitalAddress }, (results, status) => {
            if (status === 'OK' && results[0]) {
                window.hospitalMarker = new google.maps.Marker({
                    position: results[0].geometry.location,
                    map: map,
                    title: "Hospital Location",
                    icon: hospitalIcon,
                    animation: google.maps.Animation.DROP
                });

                // Add prominent info window for hospital
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding:10px;min-width:200px">
                            <h3 style="color:#d00;margin:0 0 10px 0">🏥 Hospital</h3>
                            <p style="margin:0;font-size:14px">${hospitalAddress}</p>
                        </div>
                    `,
                    maxWidth: 300
                });

                // Open info window automatically and on click
                infoWindow.open(map, window.hospitalMarker);
                window.hospitalMarker.addListener("click", () => {
                    infoWindow.open(map, window.hospitalMarker);
                });

                // Highlight exact hospital location
                const hospitalLocation = results[0].geometry.location;
                
                // Add circle overlay to precisely mark the location
                new google.maps.Circle({
                    strokeColor: '#FF0000',
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                    fillColor: '#FF0000',
                    fillOpacity: 0.35,
                    map: map,
                    center: hospitalLocation,
                    radius: 20  // 20 meters radius
                });

                // Center and zoom map on hospital with optimal view
                map.setCenter(hospitalLocation);
                map.setZoom(16);  // Close zoom for precise location
                
                // Show exact coordinates in console for verification
                console.log('Hospital coordinates:', 
                    hospitalLocation.lat(), hospitalLocation.lng());
                
                userLat = hospitalLocation.lat();
                userLng = hospitalLocation.lng();
            } else {
                alert('Hospital address not found. Please try again.');
                return;
            }
        });
    }
    
    // If location is available (either user or hospital), add it to the request
    if (userLat && userLng) {
        url += `&lat=${userLat}&lng=${userLng}&radius=${radius}`;
    } else if (!hospitalAddress) {
        alert("Please enable location services or enter a hospital address");
        return;
    }

    fetch(url)
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Fetched Donors Data:", data);
        if (!Array.isArray(data) || data.length === 0) {
            alert("No donors found in your area. Try expanding your search radius.");
            return;
        }

        data.forEach(donor => {
            if (!donor.address) {
                console.error("Missing address for:", donor);
                return;
            }

            // Use stored coordinates if available, otherwise geocode
            if (donor.latitude && donor.longitude) {
                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(donor.latitude), lng: parseFloat(donor.longitude) },
                    map: map,
                    title: `${donor.name} (${donor.blood_group})`
                });
            } else {
                // Fallback to geocoding if coordinates missing
                geocoder.geocode({ address: donor.address }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        const marker = new google.maps.Marker({
                            position: results[0].geometry.location,
                            map: map,
                            title: `${donor.name} (${donor.blood_group})`
                        });

                        // Add info window with donor details
                        const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div>
                                <h3>${donor.name}</h3>
                                <p>Blood Group: ${donor.blood_group}</p>
                                <p>Contact: ${donor.mobile_number}</p>
                                <p>Address: ${donor.address}</p>
                                ${donor.last_donation_date ? `<p>Last Donation: ${donor.last_donation_date}</p>` : ''}
                                <p>Available: ${donor.is_available ? 'Yes' : 'No'}</p>
                            </div>
                        `
                    });

                        marker.addListener("click", () => {
                            infoWindow.open(map, marker);
                        });
                    } else {
                        console.error('Geocode was not successful for:', donor.address);
                    }
                });
            }
        });
    })
    .catch(error => {
        console.error("Error fetching donor data:", error);
        alert("Failed to load donor data. Please try again later.");
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const qrImage = document.getElementById('qr-image');
    const transcriptionBox = document.getElementById('transcription');
    const refreshButton = document.getElementById('refresh-button');

    // Step 1: Load QR Code
    fetchQRCode();

    // Step 4: Poll the server every 2 seconds for transcription
    const pollInterval = 2000;
    let polling = setInterval(checkTranscription, pollInterval);

    // Variable to store the decoded QR Code value
    let qrcode_uuid = null;

    function fetchQRCode() {
        fetch('https://devone.aplikasi.web.id/one-api/voice_2text/api/qrcode')
            .then(response => response.blob())
            .then(blob => {
                qrImage.src = URL.createObjectURL(blob);

                // Decode QR Code after image is loaded
                qrImage.onload = () => {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = qrImage.width;
                    canvas.height = qrImage.height;
                    ctx.drawImage(qrImage, 0, 0, canvas.width, canvas.height);
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, canvas.width, canvas.height);

                    if (code) {
                        qrcode_uuid = code.data; // Store the decoded QR Code value
                        console.log('QR Code UUID:', qrcode_uuid);
                    } else {
                        console.error('QR Code not detected');
                    }
                };
            })
            .catch(error => console.error('Error fetching QR Code:', error));
    }

    function checkTranscription() {
        if (!qrcode_uuid) return; // Do not proceed if qrcode_uuid is not set

        // Use qrcode_uuid in the API endpoint
        fetch(`https://devone.aplikasi.web.id/one-api/voice_2text/Voicetotext/${qrcode_uuid}`)
            .then(response => response.json())
            .then(data => {
                if (data.transcription) {
                    clearInterval(polling); // Stop polling
                    transcriptionBox.value = data.transcription; // Display transcription
                }
            })
            .catch(error => console.error('Error checking transcription:', error));
    }

    // Add event listener for the "Refresh" button
    refreshButton.addEventListener('click', () => {
        fetchQRCode(); // Reload the QR Code
        transcriptionBox.value = ''; // Clear the transcription box
    });
});
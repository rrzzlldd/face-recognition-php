document.addEventListener('DOMContentLoaded', () => {
    const imageUpload = document.getElementById('imageUpload');
    const recognizeButton = document.getElementById('recognizeButton');
    const resultDiv = document.getElementById('result');
    const loadingDiv = document.getElementById('loading');
    
    // URL ini menunjuk ke skrip PHP di server yang sama
    const backendUrl = 'upload.php'; 

    recognizeButton.addEventListener('click', async () => {
        const file = imageUpload.files[0];
        if (!file) {
            resultDiv.textContent = 'Silakan pilih file gambar terlebih dahulu.';
            return;
        }

        const formData = new FormData();
        formData.append('image', file);

        loadingDiv.style.display = 'block';
        resultDiv.textContent = '';
        resultDiv.style.color = '#333';

        try {
            const response = await fetch(backendUrl, {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (data.status === 'error') {
                throw new Error(data.message);
            }
            
            const results = data.data;
            if (results && results.length > 0) {
                const resultText = results.map(res => {
                    if (res.name === 'unknown') {
                        return 'Wajah Tidak Dikenal';
                    }
                    // Jarak yang lebih kecil lebih baik. Konversi ke % kemiripan.
                    const similarity = Math.round((1 - res.distance) * 100);
                    return `${res.name} (Kemiripan: ${similarity}%)`;
                }).join(', ');
                resultDiv.textContent = `Hasil: ${resultText}`;
            } else {
                resultDiv.textContent = 'Tidak ada wajah yang dapat dideteksi di gambar ini.';
            }

        } catch (error) {
            console.error('Error:', error);
            resultDiv.textContent = `Terjadi kesalahan: ${error.message}`;
            resultDiv.style.color = 'red';
        } finally {
            loadingDiv.style.display = 'none';
        }
    });
});
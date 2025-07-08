# /var/www/html/encode_faces.py
import face_recognition
import os
import json

KNOWN_FACES_DIR = 'database_wajah'
OUTPUT_FILE = 'known_face_encodings.json'

print(f"Memproses gambar di direktori {KNOWN_FACES_DIR}...")

known_encodings = []
known_names = []

for name in os.listdir(KNOWN_FACES_DIR):
    person_dir = os.path.join(KNOWN_FACES_DIR, name)
    if not os.path.isdir(person_dir):
        continue
    
    for filename in os.listdir(person_dir):
        image_path = os.path.join(person_dir, filename)
        
        try:
            image = face_recognition.load_image_file(image_path)
            face_encodings = face_recognition.face_encodings(image)
            
            if len(face_encodings) > 0:
                encoding = face_encodings[0]
                known_encodings.append(encoding.tolist())
                known_names.append(name)
                print(f"Berhasil memproses {filename} untuk {name}")
            else:
                print(f"Peringatan: Tidak ada wajah ditemukan di {filename}")
        except Exception as e:
            print(f"Error saat memproses {filename}: {e}")

data = {"encodings": known_encodings, "names": known_names}
with open(OUTPUT_FILE, 'w') as f:
    json.dump(data, f)

print(f"\nDatabase sidik jari wajah telah disimpan ke {OUTPUT_FILE}")
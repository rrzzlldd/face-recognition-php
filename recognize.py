# /var/www/html/recognize.py
import face_recognition
import json
import sys
import numpy as np

def recognize_face(image_path):
    try:
        with open('known_face_encodings.json', 'r') as f:
            data = json.load(f)
    except FileNotFoundError:
        print(json.dumps([{"error": "Database sidik jari tidak ditemukan. Jalankan encode_faces.py"}]))
        return

    known_face_encodings = [np.array(enc) for enc in data['encodings']]
    known_face_names = data['names']

    try:
        unknown_image = face_recognition.load_image_file(image_path)
    except FileNotFoundError:
        print(json.dumps([{"error": f"File gambar tidak ditemukan di path: {image_path}"}]))
        return
        
    unknown_face_locations = face_recognition.face_locations(unknown_image)
    unknown_face_encodings = face_recognition.face_encodings(unknown_image, unknown_face_locations)

    results = []

    if not unknown_face_encodings:
        print(json.dumps([])) # Kembalikan array kosong jika tidak ada wajah terdeteksi
        return

    for face_encoding in unknown_face_encodings:
        matches = face_recognition.compare_faces(known_face_encodings, face_encoding, tolerance=0.6)
        name = "unknown"
        distance = 1.0 # Jarak terjauh

        face_distances = face_recognition.face_distance(known_face_encodings, face_encoding)
        if len(face_distances) > 0:
            best_match_index = np.argmin(face_distances)
            if matches[best_match_index]:
                name = known_face_names[best_match_index]
                distance = face_distances[best_match_index]
        
        results.append({"name": name, "distance": distance})

    print(json.dumps(results))

if __name__ == "__main__":
    if len(sys.argv) > 1:
        image_path_from_php = sys.argv[1]
        recognize_face(image_path_from_php)
    else:
        print(json.dumps([{"error": "Tidak ada path gambar yang diberikan"}]))
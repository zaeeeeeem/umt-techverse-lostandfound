# 🎒 Lost & Found Portal – UMT

A web-based Lost & Found Portal designed to help students and staff at the University of Management and Technology (UMT) report, find, and claim lost items efficiently and securely.

---

## 🧩 Problem Statement

Students often misplace personal belongings such as ID cards, water bottles, USB drives, and notebooks across campus. This system provides a centralized platform to report, search, and recover lost and found items while ensuring transparency and accountability.

---

## 🚀 Features

### 🔐 User Authentication
- Sign up/login with university email (`@umt.edu.pk` only)
- Role-based access: Students, Admin

### 📝 Lost & Found Posting
- Post lost/found item with image, location, category, description
- Date picker and image upload

### 🔎 Item Listings
- Separate Lost and Found pages
- Filter by category, location, and date
- Responsive UI for desktop and mobile

### 🔁 Claim Request System
- Claim a found item securely
- Notifications to the finder via dashboard or email

### 🧠 AI Matching (MobileNetV2 + Flask)
- Upload an image of a lost item
- AI recommends visually similar found items using MobileNetV2 and cosine similarity
- Flask API handles the feature extraction and comparison logic

### 💬 Real-Time Chat (Optional)
- In-app secure chat between item finder and claimer

### 📊 Dashboards
- User dashboard: View your posts and requests
- Admin dashboard: Moderate posts, resolve claims, archive items

---

## 🧠 AI Matching Overview

**Model Used:** MobileNetV2  
**Backend:** Flask API  
**Similarity Metric:** Cosine similarity on image embeddings  
**Usage:**  
- Image uploaded → features extracted using MobileNetV2 → compared to stored embeddings of found items → similar items suggested

---

## 🛠️ Tech Stack

| Layer       | Technology                      |
|------------|----------------------------------|
| Frontend    | HTML, CSS, Tailwind, JavaScript |
| Backend     | PHP (core logic & DB handling)  |
| Database    | MySQL                           |
| AI Service  | Python, Flask, MobileNetV2      |

---

## 📁 Folder Structure

```
├── assets/                  # Assets folder containing css, js
│   └── css
        |-  landing.css           
│   └── js
        |-  landing.js                 
├── php/                    # Backend PHP logic
├── img/                    # images used in project
├── sql                     # Containing db
├── uploads                 # Images uploaded from users (lost & found)
├── views                   # Containing view files


---

## ⚙️ Setup Instructions

### 1. Web Application (PHP)
- Place the project in `htdocs` if using XAMPP.
- Set your MySQL credentials in `db.php`.
- Ensure your Apache and MySQL services are running.

---

## ✅ Completed Functionalities
- Implement real-time chat using Ajax
- Authentication with univeristy allowed email only (@umt.edu.pk).

Students can
- Add found items.
- See lost items.
- Can request for its lost item.
- Can approve request of found items.
- Can chat with each other.

Admin  can
- Archrive/ Unarchive the post.
- Resolve the post.

---

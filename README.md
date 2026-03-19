# 🍷 Renegade Urban Winery – Event Ticketing System

## 📌 Overview

This project is a custom-built **event ticketing and validation system** implemented on top of WordPress and WooCommerce.

It transforms a standard e-commerce platform into a **fully functional event management system**, handling:

* ticket generation
* seat allocation
* QR-based validation
* real-time check-in tracking
* admin-level ticket control

The system is designed to solve real-world problems in **event organization, ticket distribution, and entry validation**.

🔗 Live demo: https://renegade-winary.infinityfreeapp.com/ <br/>
📘 Full Project Documentation (PDF): 

---
## 🎥 Demo

### Full System Overview
https://www.youtube.com/watch?v=LSsF2rVEQ9k

### Ticket Purchase Flow
https://www.youtube.com/watch?v=TJwqHD2TgBI

---

## 🛠 Tech Stack
![WordPress](https://img.shields.io/badge/WordPress-21759B?style=for-the-badge&logo=wordpress&logoColor=white)
![Elementor](https://img.shields.io/badge/Elementor-92003B?style=for-the-badge&logo=elementor&logoColor=white)
![WooCommerce](https://img.shields.io/badge/WooCommerce-96588A?style=for-the-badge&logo=woocommerce&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Dompdf](https://img.shields.io/badge/Dompdf-PDF--Gen-blue?style=for-the-badge&logo=adobeacrobatreader&logoColor=white)
![html5-qrcode](https://img.shields.io/badge/html5--qrcode-QR--Scanner-success?style=for-the-badge&logo=qrcode&logoColor=white)
![Stripe](https://img.shields.io/badge/Stripe-Payments-6772E5?style=for-the-badge&logo=stripe&logoColor=white)

---

## 🚀 Key Features

### 🎟 Automated Ticket Generation

* Unique ticket ID generation (`RW-XXXXXXX`)
* Automatic seat assignment per event
* Ticket creation triggered on WooCommerce order completion
* Multiple tickets per order supported

---

### 📄 PDF Ticket System

* Dynamic PDF generation using Dompdf
* Each ticket includes:

  * Event details
  * Seat number
  * Unique QR code
* Automatically attached to customer email

---

### 📱 QR Code Validation System

* Each ticket contains a secure QR code
* QR encodes:

  * ticket ID
  * cryptographic hash (HMAC)

Validation flow:

1. Scan QR
2. Call REST API endpoint
3. Verify hash integrity
4. Check ticket status
5. Mark ticket as **used**

Prevents:

* duplicate entry
* forged tickets

---

### 🔐 Secure Ticket Verification

* Hash-based validation using `hash_hmac`
* Prevents manual ticket ID manipulation
* Stateless verification via REST API

---

### 📊 Admin Dashboard

Custom admin panel for:

* Viewing all events

* Tracking:

  * tickets sold
  * check-ins
  * remaining capacity

* Manual ticket management:

  * cancel ticket
  * reset ticket
  * delete ticket

---

### 🔄 Real-Time Event Stats

* Live ticket statistics via REST API
* Auto-refresh every 3 seconds
* Displays:

  * total sold
  * checked-in users
  * remaining tickets

---

### 📷 QR Scanner (Admin)

* Built using `html5-qrcode`
* Works directly in browser
* No external device required

---

## 🧠 Architecture & Design Decisions

### 🧩 Why WordPress + WooCommerce?

Instead of building from scratch, WooCommerce was used for:

* payment handling (Stripe)
* order lifecycle
* email system

This allowed focusing on **business logic instead of infrastructure**.

---

### 🗂 Custom Database Tables

Default WordPress structure (`postmeta`) is not suitable for:

* relational queries
* performance-critical operations

Custom tables implemented:

* `event_tickets` → ticket storage
* `event_checkins` → scan history
* `event_seat_counter` → atomic seat allocation

---

### 🧱 Custom Post Types (CPT)

Events are modeled as a **Custom Post Type**.

Reason:

* WordPress has no native event entity
* Needed structured content (date, location, product mapping)

---

### 🧾 ACF (Advanced Custom Fields)

Used for:

* linking WooCommerce product → event
* storing event metadata (date, location)

Why:

* faster development
* flexible schema without manual admin UI building

---

### 🔐 Security Approach

* HMAC hash validation for tickets
* Sanitization of all request inputs
* Status-based validation (`valid`, `used`, `cancelled`)



---

## 📸 System Preview

### QR Validation

* Valid ticket → marked and consumed

* Used ticket → rejected

### Admin Dashboard

* Event overview
* Ticket statistics

* Manual controls
<img width="900" height="650" alt="4  All Tickets" src="https://github.com/user-attachments/assets/cbd68a58-b354-4f6a-82e7-05dfabf6ea53" />

### Email System

* Automatic ticket delivery
<img width="431" height="910" alt="image" src="https://github.com/user-attachments/assets/2ae0ce20-dc0e-4861-a1b7-b0f77237a25e" />


* PDF attachment with QR
<img width="600" height="750" alt="3  Email - QR Code" src="https://github.com/user-attachments/assets/3e2bf759-67a9-4499-90b0-1385a7e648e4" />

---

## 🧠 What This Project Demonstrates

* Ability to extend WordPress beyond CMS usage
* Designing custom data models
* Building REST APIs inside WordPress
* Handling real-world business logic (tickets, validation, concurrency)
* Integrating multiple systems (payments, email, QR, admin tools)

---

## 📬 Conclusion

This project demonstrates how WordPress can be transformed into a **custom application platform**, not just a CMS.

It solves real-world problems in:

* event organization
* digital ticketing
* entry validation

and shows practical understanding of **backend logic, system design, and integrations**.

---

# Virtual Study Group

**Virtual Study Group** is a collaborative platform designed to enable users to create, join, and manage study groups, share resources, and participate in real-time discussions. It ensures scalability and easy maintenance, offering robust features for group management, resource sharing, event participation, and real-time communication.

---

## Table of Contents

1. [Features](#features)
   - [Core Functionalities](#1-core-functionalities)
   - [Real-Time Communication](#2-real-time-communication)
   - [Comprehensive Admin Tools](#3-comprehensive-admin-tools)
2. [Screenshots](#screenshots)
3. [Installation](#installation)
   - [Prerequisites](#1-prerequisites)
   - [Steps to Install](#2-steps-to-install)
4. [MinIO Bucket Policy](#minio-bucket-policy)
5. [Chat Server Branch](#chat-server-branch)
6. [Credits](#credits)
7. [License](#license)
8. [Acknowledgments](#acknowledgments)

---

## Features

### 1. Core Functionalities

#### Group Management
- Create, join, and explore study groups.
- Manage group members, including role assignments (Admin, Co-admin, Member).
- Pin and unpin groups for quick access.
- Handle join requests and ban/unban members.

#### User Profile
- View and edit personal profiles, including uploading profile pictures.
- Track points and profile completion progress.
- View other users’ profiles.

#### Resource Sharing
- Upload and share resources within groups.
- Vote on resources to highlight useful content.
- Manage and delete uploaded resources.

#### Events
- Create and participate in group-specific events.
- RSVP to events with options: Yes, No, Maybe.
- Handle recurring events and set reminders.

### 2. Real-Time Communication

#### WebSocket-Based Chat
- Real-time messaging for group discussions.
- Support for structured messages (text and resources).
- Powered by a dedicated chat server using PHP and Ratchet.

### 3. Comprehensive Admin Tools

- Manage permissions for Co-admins.
- Monitor and moderate group activities.
- Audit user activity and resource voting.

---

## Screenshots

<details>
<summary>Dashboard</summary>
<img src="assets/screenshots/dashboard.png" alt="Dashboard">
</details>

<details>
<summary>Group Creation</summary>
<img src="assets/screenshots/create_group.png" alt="Create Group">
</details>

<details>
<summary>Event Participation</summary>
<img src="assets/screenshots/show_all_events.png" alt="Events">
</details>

<details>
<summary>Real-Time Chat</summary>
<img src="assets/screenshots/chat_window.png" alt="Chat">
</details>

<details>
<summary>User Profile</summary>
<img src="assets/screenshots/user_profile(own).png" alt="User Profile">
</details>

<details>
<summary>All Screenshots</summary>
<ul>
    <li><img src="assets/screenshots/all_group_members.png" alt="All Group Members"></li>
    <li><img src="assets/screenshots/banded_group_members.png" alt="Banned Group Members"></li>
    <li><img src="assets/screenshots/edit_group_info.png" alt="Edit Group Info"></li>
    <li><img src="assets/screenshots/group.png" alt="Group"></li>
    <li><img src="assets/screenshots/group_info.png" alt="Group Info"></li>
    <li><img src="assets/screenshots/group_leave_acction_for_admin.png" alt="Group Leave Action for Admin"></li>
    <li><img src="assets/screenshots/group_menu_modal.png" alt="Group Menu Modal"></li>
    <li><img src="assets/screenshots/home.png" alt="Home"></li>
    <li><img src="assets/screenshots/login.png" alt="Login"></li>
    <li><img src="assets/screenshots/manage_co-admin_permissions.png" alt="Manage Co-Admin Permissions"></li>
    <li><img src="assets/screenshots/manage_group_members.png" alt="Manage Group Members"></li>
    <li><img src="assets/screenshots/manage_join_request.png" alt="Manage Join Requests"></li>
    <li><img src="assets/screenshots/pin_unpin_group_modal.png" alt="Pin/Unpin Group Modal"></li>
    <li><img src="assets/screenshots/register.png" alt="Register"></li>
    <li><img src="assets/screenshots/resource_up-vote.png" alt="Resource Up-Vote"></li>
    <li><img src="assets/screenshots/user_point_history_table.png" alt="User Point History"></li>
    <li><img src="assets/screenshots/user_profile(others).png" alt="User Profile (Others)"></li>
    <li><img src="assets/screenshots/database_tables.png" alt="Database Tables"></li>
    <li><img src="assets/screenshots/database_triggers.png" alt="Database Triggers"></li>
    <li><img src="assets/screenshots/minio_obj_store.png" alt="MinIO Object Store"></li>
    <li><img src="assets/screenshots/minio_obj_store_bucket.png" alt="MinIO Object Store Bucket"></li>
</ul>
</details>

---

## Installation

### 1. Prerequisites
- **Backend**: PHP 7.4 or higher.
- **Database**: MySQL/MariaDB.
- **Object Storage**: MinIO (for resource and profile picture uploads).
- **WebSocket**: Ratchet (for real-time chat).
- **Composer**: Dependency manager for PHP.

### 2. Steps to Install
1. Clone the repository:
   ```bash
   git clone https://github.com/ZIDAN44/VirtualStudyGroup.git
   cd VirtualStudyGroup
   ```

2. Configure the `.env` file:
   ```bash
   cp .env.sample .env
   # Update database and MinIO credentials
   ```

3. Import the database schema:
   ```bash
   mysql -u [username] -p [database_name] < database/studygroup(schema).sql
   ```

4. Install dependencies:
   ```bash
   composer install
   ```

5. Start the WebSocket server:
   ```bash
   php chat_server.php
   ```

6. Run the project:
   - Use your preferred local server setup (e.g., Apache, Nginx, or PHP’s built-in server).

---

## MinIO Bucket Policy

### Purpose

The `bucket_policy.json` file ensures that the required buckets in MinIO have the correct read and write permissions for smooth operation. This policy:
- Grants public read access for uploaded resources (e.g., profile pictures, group resources).
- Restricts write access to authenticated users.

### How to Use

1. Configure the bucket policy in MinIO:
   ```bash
   mc alias set local http://localhost:9000 ACCESS_KEY SECRET_KEY
   mc policy set-json bucket_policy.json local/[bucket-name]
   ```

2. Replace `[bucket-name]` with your bucket's name.

---

## Chat Server

This project includes a **real-time chat server** implemented in the `chat-server` branch. The server is powered by Ratchet for WebSocket-based communication. Please visit the [chat-server README](https://github.com/ZIDAN44/VirtualStudyGroup/tree/chat-server) for detailed setup instructions and usage examples.

---

## Credits

Special thanks to the contributors for making this project possible:

- [**Zinadin Zidan**](https://github.com/ZIDAN44)
- [**Labeeb Ashhab**](https://github.com/LabeebAshhab)
- [**Nafis Islam**](https://github.com/Nafis-Rohan)
- [**Asif Hossain Joy**](https://github.com/Cyberdoc-Joy)

---

## License

This project is licensed under the [MIT License](LICENSE).

---

## Acknowledgments

- **[Ratchet](https://socketo.me/)**: WebSocket library.
- **MinIO**: Object storage for resources.
- All contributors to the Virtual Study Group project.

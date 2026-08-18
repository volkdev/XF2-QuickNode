# [VolkDev] Quick Node Creator – XenForo 2 Frontend Node Management Add-on 🚀

[![XenForo Version](https://img.shields.io/badge/XenForo-2.2%20%7C%202.3-blue.svg)](https://xenforo.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

**[🇷🇺 Русская версия (Russian Version)](README_RU.md)**

**[VolkDev] Quick Node Creator** is a comprehensive and powerful **XenForo 2 add-on** that brings the complex task of forum structure management—creating, editing, and deleting nodes and categories—directly to the front-end interface.

This XenForo plugin allows administrators to safely delegate forum and category management to local moderators, project leaders, or curators without ever exposing them to the sensitive Admin Control Panel (ACP). It is the perfect solution for large community hubs, gaming portals, roleplay servers, or multi-guild platforms running on XenForo!

---

## 🚀 Why Use Quick Node Creator?
Managing a constantly evolving forum structure can become a bottleneck for root administrators. With Quick Node Creator, you can empower your trusted team members to build and manage their own categories directly from the forum index. Designed with strict adherence to XenForo 2 MVC standards, it guarantees high performance, secure database transactions, and zero template conflicts.

## ✨ Key Features & Capabilities

### 🛠️ Full Front-End Node Management
- **Create, Edit, Delete**: Authorized users can intuitively create, modify, and delete forums, categories, and link-forums natively from the public forum view.
- **Fast AJAX Interface**: Seamlessly integrated with XenForo's modal windows for a smooth user experience.

### 🔐 Granular XenForo Permissions
- **Native Integration**: Ties directly into the XenForo permission system.
- **Global or Local Scope**: Grant node management permissions globally across the board, or restrict them to specific parent nodes for dedicated local curators.

### 🛡️ Secure "Pending Deletion" & Approval Workflow
- **Vandalism Protection**: A built-in fail-safe against accidental or malicious deletions.
- **Approval System**: When a front-end moderator deletes a node, it isn't wiped from the database. Instead, it gets hidden and marked as "Pending Deletion." Root administrators can review these requests in the ACP to easily approve or reject them.

### 👁️ 1-Click Private Nodes
- **Easy Access Control**: Create "Private" nodes instantly with a single checkbox during creation.
- **Automated Permissions**: The add-on safely manages XenForo's complex `viewNode` permissions in the background, restricting visibility exclusively to the node creator and globally allowed administrative groups.

### 📋 Comprehensive Logging & 1-Click Revert System
- **Detailed Audit Logs**: Every front-end action (node creation, edits, permission changes, deletions) is deeply logged.
- **Instant Rollback**: In the ACP, administrators can view the exact JSON payload of the *before* and *after* states and **revert** any action with one click, restoring the node's previous data, settings, and permissions instantly.

### 🚫 Protected User Groups
- **Permission Fencing**: An extra layer of security ensures that front-end node managers cannot manipulate the permissions of crucial administrative user groups (configurable via `qnc_protected` tags).

### ⚡ XenForo 2.3 Ready
- Written using native XenForo Javascript helpers and AJAX forms, ensuring full compatibility with both legacy XenForo versions and the new vanilla JS architecture in XenForo 2.3.

---

## 📸 Screenshots

### Node Creation Modal
![Create Node Modal](https://i.imgur.com/lKl6n1T.png)

### Group Permissions Management
![Permissions Editor](https://i.imgur.com/lR9Bez0.png)

### ACP Action Logs & Revert System
![ACP Logs](https://i.imgur.com/FjzPMvQ.png)

---

## 📥 Installation Guide
1. Download the latest `.zip` archive release from the [Releases](../../releases) page.
2. Extract the contents of the `upload` folder and upload it into the root directory of your XenForo installation.
3. Log into the XenForo Admin Control Panel and go to **Add-ons**.
4. Click Install on **[VolkDev] Quick Node Creator**.

## ⚙️ XenForo Configuration
1. Navigate to **Groups & Permissions -> Node permissions** or **User group permissions**.
2. Locate the **Quick Node Creator** section and grant the desired permissions:
   * *Can create nodes from the front-end*
   * *Can edit nodes from the front-end*
   * *Can delete nodes from the front-end*
   * *Can manage group permissions from the front-end*
3. Navigate to **Options -> Quick Node Creator** to configure administrative groups that can consistently bypass private node visibility restrictions.

## 📄 License
This XenForo add-on is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

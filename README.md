# [VolkDev] Quick Node Creator 🚀

[![XenForo Version](https://img.shields.io/badge/XenForo-2.2%20%7C%202.3-blue)](https://xenforo.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

**[🇷🇺 Русская версия (Russian Version)](README_RU.md)**

**Quick Node Creator** is a comprehensive and powerful XenForo 2 add-on that brings the complex task of node (forum/category) management directly to the front-end. It allows administrators to safely delegate forum structural management to local moderators or curators without ever giving them access to the Admin Control Panel (ACP).

This add-on is designed with strict adherence to XenForo 2 MVC standards and best practices, ensuring maximum security, high performance, and zero template conflicts.

## ✨ Key Features
* **Full Front-End Node Management:** Authorized users can intuitively create, edit, and delete forums, categories, and link-forums straight from the public forum view.
* **Granular XenForo Permissions:** Seamlessly integrates with the native XenForo permission system. You can grant node management permissions globally, or restrict them to specific parent nodes for local curators.
* **Pending Deletion & Approval Workflow:** A built-in fail-safe against abuse. When a front-end moderator deletes a node, it isn't wiped from the database. Instead, it gets hidden and marked as "Pending Deletion." Administrators can review these requests in the ACP and either approve or reject them.
* **Private Nodes Made Easy:** Create "Private" nodes with a single checkbox. The add-on safely manages XenForo's complex `viewNode` permissions in the background, keeping the node visible only to the creator and globally allowed groups.
* **Comprehensive Logging & 1-Click Revert:** Every single action (creation, edits, permission changes, deletions) is deeply logged. In the ACP, administrators can view the exact JSON payload of the *before* and *after* states and instantly **revert** any action with one click, restoring the node's previous data and permissions.
* **Protected Groups:** An extra layer of security that prevents front-end permission managers from modifying the permissions of crucial administrative user groups.
* **XenForo 2.3 Ready:** Written using native XenForo Javascript helpers and AJAX forms, meaning it's fully compatible with the new XenForo 2.3 vanilla JS architecture.

## 📸 Screenshots

### Node Creation Modal
![Create Node Modal](https://i.imgur.com/lKl6n1T.png)

### Group Permissions Management
![Permissions Editor](https://i.imgur.com/lR9Bez0.png)

### ACP Action Logs & Revert System
![ACP Logs](https://imgur.com/FjzPMvQ)

## 📥 Installation
1. Download the latest `.zip` archive from the [Releases](../../releases) page.
2. Extract the contents of the `upload` folder into the root directory of your XenForo installation.
3. Go to the Admin Control Panel -> **Add-ons**.
4. Click Install on **[VolkDev] Quick Node Creator**.

## ⚙️ Configuration
1. Navigate to **Groups & Permissions -> Node permissions** or **User group permissions**.
2. Grant the desired groups or users the permissions under the **Quick Node Creator** section:
   * *Can create nodes from the front-end*
   * *Can edit nodes from the front-end*
   * *Can delete nodes from the front-end*
   * *Can manage group permissions from the front-end*
3. Navigate to **Options -> Quick Node Creator** to configure groups that can always bypass private node restrictions (e.g. Administrators).

## 📄 License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

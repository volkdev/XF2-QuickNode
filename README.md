# [VolkDev] Quick Node Creator 🚀

[![XenForo Version](https://img.shields.io/badge/XenForo-2.2%20%7C%202.3-blue)](https://xenforo.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

**[🇷🇺 Русская версия (Russian Version)](README_RU.md)**

**Quick Node Creator** is a powerful XenForo 2 add-on that allows your forum moderators to manage the node tree directly from the front-end, without needing access to the Admin Control Panel (ACP).

It is built perfectly for large communities that rely on regional or category curators, offering native XenForo node creation with an extensive permissions and logging system.

## ✨ Features
* **Front-End Node Management:** Authorized users can create, edit, and delete sub-nodes directly from the forum interface.
* **Granular Permissions:** Full integration with XenForo's permission system. Grant permissions to create/edit/delete nodes or manage node permissions on a per-node or global basis.
* **Pending Deletion Approval:** To prevent abuse, when a moderator deletes a node, it is first marked as "Pending Deletion" and becomes hidden. Administrators must approve the deletion via the ACP logs.
* **Private Nodes:** Easily mark nodes as "Private", hiding them from regular users while keeping them visible to authorized groups.
* **Local Moderators:** Quickly assign users as local node moderators directly from the front-end.
* **Comprehensive Action Logs & Revert System:** Every action (creation, edits, permission changes, deletions) is logged in the ACP. Administrators can view detailed before/after states and **revert** actions with a single click.
* **Protected Groups:** Protect administrative groups from being edited by front-end moderators.

## 📥 Installation
1. Download the latest `.zip` archive from the [Releases](../../releases) page.
2. Extract the contents of the `upload` folder into the root directory of your XenForo installation.
3. Go to the Admin Control Panel -> **Add-ons**.
4. Install **[VolkDev] Quick Node Creator**.

## ⚙️ Configuration
1. Go to **Groups & Permissions -> Node permissions** or **User group permissions**.
2. Grant the desired groups or users the permissions under the **Quick Node Creator** section:
   * *Can create nodes from the front-end*
   * *Can edit nodes from the front-end*
   * *Can delete nodes from the front-end*
   * *Can manage group permissions from the front-end*
3. Go to **Options -> Quick Node Creator** to configure groups that can always bypass private node restrictions.

## 📸 Screenshots
*(Add your screenshots here)*
* `![Create Node Modal](link)`
* `![ACP Logs](link)`
* `![Permissions Editor](link)`

## 📄 License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

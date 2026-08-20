# Quick Node Creator for XenForo 2

[![XenForo Version](https://img.shields.io/badge/XenForo-2.2%20%7C%202.3-blue.svg)](https://xenforo.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

**[Русская версия](README_RU.md)**

**Quick Node Creator** is a XenForo 2 add-on that allows users to create, edit, and delete forums and categories directly from the public forum interface, bypassing the need for Admin Control Panel access.

This plugin is built to delegate forum structure management. It enables you to assign moderators or community leaders who can independently manage their own categories and node trees.

## Features

* **Frontend Node Management:** Create, modify, and delete forums, categories, and link-forums natively from the public view.
* **Permission Templates:** Administrators can pre-configure permission presets in the ACP that frontline moderators can apply to their nodes with a single click.
* **Permission Integration:** Uses the native XenForo permission system. Node management rights can be granted globally or restricted to specific parent nodes.
* **Deletion Approval System:** When a moderator deletes a node, it is hidden rather than removed from the database. Root administrators can review, approve, or revert the deletion from the admin panel.
* **Private Nodes:** A single checkbox during node creation configures the permissions so the forum is only visible to the creator and selected administrative groups.
* **Protected Groups:** Prevents frontend moderators from altering the permissions of critical system groups or administrators (configurable via ACP).
* **Audit Logs and Rollbacks:** All actions are logged. Administrators can view the exact before-and-after states (with clean human-readable permission translations) and revert any changes with one click.

## Screenshots

### Node Creation Modal
![Create Node Modal](https://i.imgur.com/lKl6n1T.png)

### Group Permissions Management
![Permissions Editor](https://i.imgur.com/lR9Bez0.png)

### ACP Action Logs & Revert System
![ACP Logs](https://i.imgur.com/FjzPMvQ.png)

## Installation
1. Download the `.zip` archive from the [Releases](../../releases) page.
2. Upload the contents of the `upload` folder into the root directory of your XenForo installation.
3. Go to **Add-ons** in the XenForo Admin Control Panel.
4. Click Install on **[VolkDev] Quick Node Creator**.

## Configuration
1. Navigate to **Groups & Permissions -> Node permissions** or **User group permissions**.
2. Locate the **Quick Node Creator** section to grant permissions:
   * *Can create nodes from the front-end*
   * *Can edit nodes from the front-end*
   * *Can delete nodes from the front-end*
   * *Can manage group permissions from the front-end*
3. Navigate to **Options -> Quick Node Creator** to configure administrative groups that can bypass private node restrictions.



## License
Licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

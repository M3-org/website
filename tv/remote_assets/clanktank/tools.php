<?php
/**
 * Avatar Tools Interface
 * 
 * A user-friendly interface for previewing and downloading avatars
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avatar Tools</title>
    <!-- Model Viewer for 3D GLB files -->
    <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.0.1/model-viewer.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .avatar-card {
            margin-bottom: 30px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .avatar-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .headshot-container {
            flex: 0 0 200px;
            height: 200px;
            overflow: hidden;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
        }
        .headshot-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .model-container {
            display: none; /* Hidden by default */
            width: 100%;
            margin-top: 10px;
        }
        .preview-btn-container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 150px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        model-viewer {
            width: 100%;
            height: 300px;
            background-color: #f8f9fa;
            margin-bottom: 15px;
            border-radius: 5px;
            --poster-color: transparent;
        }
        .code-block {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            overflow-x: auto;
            font-family: 'Courier New', Courier, monospace;
        }
        .btn-group {
            margin-bottom: 15px;
        }
        .nav-tabs {
            margin-bottom: 20px;
        }
        .character-json {
            max-height: 500px;
            overflow-y: auto;
        }
        .pitch-item {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        .remove-pitch-btn {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .save-status {
            display: none;
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        /* Full-screen download message overlay */
        .download-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .download-overlay h2 {
            margin-bottom: 20px;
            font-size: 28px;
        }
        .download-overlay p {
            font-size: 18px;
            max-width: 600px;
        }
        .download-spinner {
            margin-bottom: 30px;
            width: 80px;
            height: 80px;
            border: 8px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spinner 1.5s linear infinite;
        }
        @keyframes spinner {
            to {transform: rotate(360deg);}
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Avatar Tools</h1>
        
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="browse-tab" data-bs-toggle="tab" data-bs-target="#browse" type="button" role="tab">Browse Avatars</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="help-tab" data-bs-toggle="tab" data-bs-target="#help" type="button" role="tab">API Help</button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <!-- Browse All Avatars Tab -->
            <div class="tab-pane fade show active" id="browse" role="tabpanel" aria-labelledby="browse-tab">
                <div class="mb-3">
                    <button id="download-all-btn" class="btn btn-primary">Download All Avatars</button>
                </div>
                
                <div id="all-avatars-container">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Loading avatars...</p>
                    </div>
                </div>
            </div>
            
            <!-- API Help Tab -->
            <div class="tab-pane fade" id="help" role="tabpanel" aria-labelledby="help-tab">
                <h3>API Usage</h3>
                <p>This system provides a simple API for accessing avatar information. Here's how to use it:</p>
                
                <h4>Endpoints:</h4>
                <ul>
                    <li><strong>getAllInfo</strong> - Get all media information (commercials, reels, avatars)</li>
                    <li><strong>getAvatar</strong> - Get information for a specific avatar</li>
                    <li><strong>getAllAvatars</strong> - Get information for all avatars</li>
                </ul>
                
                <h4>Example JavaScript Usage:</h4>
                <div class="code-block mb-4">
<pre>// Get all avatars
fetch('api.php?method=getAllAvatars')
  .then(response => response.json())
  .then(data => {
    console.log('All avatars:', data);
  });

// Get a specific avatar
fetch('api.php?method=getAvatar&id=avatar_name')
  .then(response => response.json())
  .then(data => {
    console.log('Avatar info:', data);
  });</pre>
                </div>
                
                <h4>Response Format for getAvatar:</h4>
                <div class="code-block mb-4">
<pre>{
  "name": "avatar_name",
  "headshot": "/avatars/avatar_name/avatar_name.png",
  "model": "/avatars/avatar_name/avatar_name.glb",
  "character": {
    // Content of the character JSON file
  }
}</pre>
                </div>
                
                <h3>README</h3>
                <div class="code-block" id="readme-content">
                    <p>Loading README...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Download preparation overlay -->
    <div id="download-overlay" class="download-overlay" style="display: none;">
        <div class="download-spinner"></div>
        <h2>Preparing Your Download</h2>
        <p>Your download is being prepared and should start in a few moments. Please do not close this window.</p>
    </div>
    
    <!-- Templates -->
    <template id="avatar-card-template">
        <div class="avatar-card card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="avatar-name mb-0"></h3>
                <div>
                    <button class="download-btn btn btn-primary btn-sm">Download</button>
                </div>
            </div>
            <div class="card-body">
                <div class="avatar-preview">
                    <div class="headshot-container">
                        <img class="avatar-headshot" alt="Avatar Headshot">
                    </div>
                    <div class="flex-grow-1">
                        <div class="preview-btn-container">
                            <button class="btn btn-outline-secondary preview-model-btn">PREVIEW 3D MODEL</button>
                        </div>
                        <div class="model-container">
                            <model-viewer class="avatar-model" camera-controls auto-rotate shadow-intensity="1"></model-viewer>
                        </div>
                    </div>
                </div>
                
                <ul class="nav nav-tabs mt-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active details-tab" data-bs-toggle="tab" data-bs-target="" type="button" role="tab">Details</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link json-tab" data-bs-toggle="tab" data-bs-target="" type="button" role="tab">JSON View</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link editor-tab" data-bs-toggle="tab" data-bs-target="" type="button" role="tab">Edit Character</button>
                    </li>
                </ul>
                
                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active details-pane" role="tabpanel">
                        <table class="table">
                            <tr>
                                <th>Name:</th>
                                <td class="avatar-detail-name"></td>
                            </tr>
                            <tr>
                                <th>Headshot:</th>
                                <td class="avatar-detail-headshot"></td>
                            </tr>
                            <tr>
                                <th>Model:</th>
                                <td class="avatar-detail-model"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="tab-pane fade json-pane" role="tabpanel">
                        <pre class="character-json"></pre>
                    </div>
                    <div class="tab-pane fade editor-pane" role="tabpanel">
                        <form class="character-editor-form">
                            <div class="mb-3">
                                <label for="character-name" class="form-label">Name:</label>
                                <input type="text" class="form-control character-name-input" id="character-name">
                            </div>
                            <div class="mb-3">
                                <label for="character-description" class="form-label">Description:</label>
                                <textarea class="form-control character-description-input" id="character-description" rows="8"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pitches:</label>
                                <div class="pitch-container">
                                    <!-- Pitch items will be added here dynamically -->
                                </div>
                                <button type="button" class="btn btn-outline-secondary add-pitch-btn mt-2">Add Pitch</button>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2 cancel-edit-btn">Cancel</button>
                                <button type="button" class="btn btn-primary save-character-btn">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the full path base URL - includes the directory path where tools.php is located
            const fullPathUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
            console.log("Base URL for assets:", fullPathUrl);
            
            // Load all avatars on page load
            loadAllAvatars();
            
            // Load README
            loadReadme();
            
            // Event listener for download all button
            document.getElementById('download-all-btn').addEventListener('click', function() {
                downloadAllAvatars();
            });
        });
        
        // Load README.txt content
        function loadReadme() {
            // Use the full path URL to ensure correct path resolution
            const fullPathUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
            fetch(fullPathUrl + 'readme.txt')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('README.txt not found');
                    }
                    return response.text();
                })
                .then(text => {
                    // Preserve tabs and line breaks by using <pre> tag
                    document.getElementById('readme-content').innerHTML = '<pre style="white-space: pre; overflow-x: auto;">' + 
                        text.replace(/</g, '&lt;').replace(/>/g, '&gt;') + 
                        '</pre>';
                })
                .catch(error => {
                    document.getElementById('readme-content').innerHTML = '<p>README.txt not found.</p>';
                });
        }
        
        // Load all avatars
        function loadAllAvatars() {
            // Use the full path URL to ensure correct path resolution
            const fullPathUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
            
            // Add cache buster to prevent caching of the request
            const cacheBuster = new Date().getTime();
            fetch(`${fullPathUrl}api.php?method=getAllAvatars&_=${cacheBuster}`)
                .then(response => response.json())
                .then(avatars => {
                    const container = document.getElementById('all-avatars-container');
                    container.innerHTML = '';
                    
                    if (avatars.length === 0) {
                        container.innerHTML = '<p>No avatars found.</p>';
                        return;
                    }
                    
                    avatars.forEach(avatar => {
                        container.appendChild(createAvatarCard(avatar, fullPathUrl));
                    });
                })
                .catch(error => {
                    console.error('Error loading avatars:', error);
                    document.getElementById('all-avatars-container').innerHTML = '<p>Error loading avatars. Please check the console for details.</p>';
                });
        }
        
        // Create an avatar card
        function createAvatarCard(avatar, fullPathUrl) {
            const template = document.getElementById('avatar-card-template');
            const card = template.content.cloneNode(true);
            
            // Set unique IDs for tabs
            const uniqueId = 'avatar-' + avatar.name.replace(/[^a-z0-9]/gi, '-').toLowerCase();
            const detailsTab = card.querySelector('.details-tab');
            const jsonTab = card.querySelector('.json-tab');
            const editorTab = card.querySelector('.editor-tab');
            const detailsPane = card.querySelector('.details-pane');
            const jsonPane = card.querySelector('.json-pane');
            const editorPane = card.querySelector('.editor-pane');
            
            detailsTab.setAttribute('data-bs-target', '#' + uniqueId + '-details');
            jsonTab.setAttribute('data-bs-target', '#' + uniqueId + '-json');
            editorTab.setAttribute('data-bs-target', '#' + uniqueId + '-editor');
            detailsPane.id = uniqueId + '-details';
            jsonPane.id = uniqueId + '-json';
            editorPane.id = uniqueId + '-editor';
            
            // Fill in the data
            card.querySelector('.avatar-name').textContent = avatar.name;
            
            const headshot = card.querySelector('.avatar-headshot');
            if (avatar.headshot) {
                // Ensure full path URL for headshot by combining the fullPathUrl with the relative path
                const headshotPath = fullPathUrl + avatar.headshot;
                headshot.src = headshotPath;
                headshot.alt = avatar.name + ' headshot';
            } else {
                headshot.src = fullPathUrl + 'placeholder.png';
                headshot.alt = 'No headshot available';
            }
            
            const modelViewer = card.querySelector('.avatar-model');
            const modelContainer = card.querySelector('.model-container');
            const previewButton = card.querySelector('.preview-model-btn');
            const previewBtnContainer = card.querySelector('.preview-btn-container');
            
            if (avatar.model) {
                // Model exists but isn't loaded until preview button is clicked
                const modelPath = fullPathUrl + avatar.model;
                
                // Add click event for preview button
                previewButton.addEventListener('click', function() {
                    // Load the model
                    modelViewer.src = modelPath;
                    
                    // Show the model and hide the preview button container
                    modelContainer.style.display = 'block';
                    previewBtnContainer.style.display = 'none';
                });
            } else {
                // No model available
                previewButton.disabled = true;
                previewButton.textContent = 'NO 3D MODEL AVAILABLE';
            }
            
            // Details tab
            card.querySelector('.avatar-detail-name').textContent = avatar.name;
            card.querySelector('.avatar-detail-headshot').textContent = avatar.headshot || 'N/A';
            card.querySelector('.avatar-detail-model').textContent = avatar.model || 'N/A';
            
            // JSON tab
            const characterJson = card.querySelector('.character-json');
            if (avatar.character) {
                // Include the complete avatar data in JSON view with cache buster timestamp
                const fullJsonData = {
                    name: avatar.name,
                    headshot: avatar.headshot,
                    model: avatar.model,
                    character: avatar.character,
                    _timestamp: new Date().getTime() // Cache buster
                };
                characterJson.textContent = JSON.stringify(fullJsonData, null, 2);
                
                // Setup editor tab
                setupCharacterEditor(card, avatar, fullPathUrl);
            } else {
                const basicData = {
                    name: avatar.name,
                    headshot: avatar.headshot,
                    model: avatar.model,
                    _timestamp: new Date().getTime() // Cache buster
                };
                characterJson.textContent = JSON.stringify(basicData, null, 2);
                // Disable editor tab if no character data
                card.querySelector('.editor-tab').classList.add('disabled');
            }
            
            // Download button
            card.querySelector('.download-btn').addEventListener('click', function() {
                downloadAvatar(avatar);
            });
            
            // Store avatar in card for reference
            card.avatar = avatar;
            
            return card;
        }
        
        // Setup character editor form
        function setupCharacterEditor(card, avatar, fullPathUrl) {
            const form = card.querySelector('.character-editor-form');
            const nameInput = card.querySelector('.character-name-input');
            const descInput = card.querySelector('.character-description-input');
            const pitchContainer = card.querySelector('.pitch-container');
            const addPitchBtn = card.querySelector('.add-pitch-btn');
            const saveBtn = card.querySelector('.save-character-btn');
            const cancelBtn = card.querySelector('.cancel-edit-btn');
            
            // Store a reference to the form in the card for later use
            card.editorForm = form;
            
            // Initialize the form with character data
            function initializeForm() {
                // Clear previous data
                pitchContainer.innerHTML = '';
                
                if (avatar.character) {
                    nameInput.value = avatar.character.name || '';
                    descInput.value = avatar.character.description || '';
                    
                    // Add pitch items
                    if (avatar.character.pitches && Array.isArray(avatar.character.pitches)) {
                        avatar.character.pitches.forEach((pitch, index) => {
                            addPitchItem(pitch, index);
                        });
                    }
                }
            }
            
            // Add a pitch item to the form
            function addPitchItem(pitch = { project: '', pitch: '' }, index) {
                const pitchItem = document.createElement('div');
                pitchItem.className = 'pitch-item';
                pitchItem.dataset.index = index;
                
                pitchItem.innerHTML = `
                    <button type="button" class="btn-close remove-pitch-btn" aria-label="Remove pitch"></button>
                    <div class="mb-3">
                        <label class="form-label">Project Name:</label>
                        <input type="text" class="form-control pitch-project-input" value="${pitch.project || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pitch Content:</label>
                        <textarea class="form-control pitch-content-input" rows="6">${pitch.pitch || ''}</textarea>
                    </div>
                `;
                
                pitchContainer.appendChild(pitchItem);
                
                // Add event listener for remove button
                pitchItem.querySelector('.remove-pitch-btn').addEventListener('click', function() {
                    pitchItem.remove();
                });
            }
            
            // Add event listener for adding a new pitch
            addPitchBtn.addEventListener('click', function() {
                const newIndex = pitchContainer.querySelectorAll('.pitch-item').length;
                addPitchItem({ project: '', pitch: '' }, newIndex);
            });
            
            // Add event listener for saving character data
            saveBtn.addEventListener('click', function() {
                // Collect data from form
                const updatedCharacter = {
                    name: nameInput.value,
                    description: descInput.value,
                    pitches: []
                };
                
                // Collect pitches
                const pitchItems = pitchContainer.querySelectorAll('.pitch-item');
                pitchItems.forEach(item => {
                    updatedCharacter.pitches.push({
                        project: item.querySelector('.pitch-project-input').value,
                        pitch: item.querySelector('.pitch-content-input').value
                    });
                });
                
                // Ensure the card has the reference to the form
                if (!card.editorForm) {
                    card.editorForm = form;
                }
                
                // Save the updated character data
                saveCharacterData(avatar.name, updatedCharacter, fullPathUrl, card);
            });
            
            // Cancel button resets the form
            cancelBtn.addEventListener('click', function() {
                initializeForm();
            });
            
            // Initialize the form
            initializeForm();
        }
        
        // Save character data to the server
        function saveCharacterData(avatarId, characterData, fullPathUrl, card) {
            // First, create a status message to show feedback
            const editorPane = card.querySelector('.editor-pane');
            let statusElement;
            
            try {
                // Try to create a status element where it will be visible
                statusElement = document.createElement('div');
                statusElement.className = 'save-status alert alert-info';
                statusElement.textContent = 'Saving changes...';
                statusElement.style.display = 'block';
                
                // Find somewhere to add it - try the form or the editor pane
                if (card.editorForm) {
                    card.editorForm.appendChild(statusElement);
                } else if (editorPane) {
                    editorPane.appendChild(statusElement);
                } else {
                    // If we can't find a place, just add it to the card body
                    const cardBody = card.querySelector('.card-body');
                    if (cardBody) {
                        cardBody.appendChild(statusElement);
                    }
                }
            } catch (error) {
                console.warn('Could not create status element:', error);
                // Continue with the save operation even if we can't show status
            }
            
            // Send the data to the server
            fetch(`${fullPathUrl}save_character.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    avatarId: avatarId,
                    character: characterData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update the character data in the avatar object
                    const jsonElement = card.querySelector('.character-json');
                    if (jsonElement) {
                        // Create the full JSON data to update the view with cache buster
                        const fullJsonData = {
                            name: card.avatar.name,
                            headshot: card.avatar.headshot,
                            model: card.avatar.model,
                            character: characterData,
                            _timestamp: new Date().getTime() // Cache buster
                        };
                        jsonElement.textContent = JSON.stringify(fullJsonData, null, 2);
                    }
                    
                    // Update the avatar object
                    if (card.avatar) {
                        card.avatar.character = characterData;
                    }
                    
                    // Show success message
                    if (statusElement) {
                        statusElement.className = 'save-status alert alert-success';
                        statusElement.textContent = 'Changes saved successfully!';
                        
                        // Remove success message after a few seconds
                        setTimeout(() => {
                            if (statusElement && statusElement.parentNode) {
                                statusElement.parentNode.removeChild(statusElement);
                            }
                        }, 3000);
                    } else {
                        alert('Changes saved successfully!');
                    }
                } else {
                    // Show error message
                    if (statusElement) {
                        statusElement.className = 'save-status alert alert-danger';
                        statusElement.textContent = 'Error: ' + (data.message || 'Failed to save changes');
                    } else {
                        alert('Error: ' + (data.message || 'Failed to save changes'));
                    }
                }
            })
            .catch(error => {
                console.error('Error saving character data:', error);
                // Show error message
                if (statusElement && statusElement.parentNode) {
                    statusElement.className = 'save-status alert alert-danger';
                    statusElement.textContent = 'Error: ' + error.message;
                } else {
                    alert('Error saving character data: ' + error.message);
                }
            });
        }
        
        // Show download preparation overlay
        function showDownloadOverlay() {
            const overlay = document.getElementById('download-overlay');
            overlay.style.display = 'flex';
            
            // Hide the overlay after a reasonable timeout
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 8000); // 8 seconds should be enough for most downloads to start
        }
        
        // Download a single avatar as a ZIP
        function downloadAvatar(avatar) {
            // Show the download preparation overlay
            showDownloadOverlay();
            
            // Use the full path URL to ensure correct path resolution
            const fullPathUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
            const downloadLink = document.createElement('a');
            downloadLink.href = `${fullPathUrl}download.php?avatar=${encodeURIComponent(avatar.name)}`;
            downloadLink.download = avatar.name + '.zip';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        // Download all avatars as a ZIP
        function downloadAllAvatars() {
            // Show the download preparation overlay
            showDownloadOverlay();
            
            // Use the full path URL to ensure correct path resolution
            const fullPathUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
            const downloadLink = document.createElement('a');
            downloadLink.href = fullPathUrl + 'download.php?all=true';
            downloadLink.download = 'all-avatars.zip';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>
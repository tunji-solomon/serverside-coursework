

function addTrack(element, emptyMessage=null) {

    const container = document.getElementById(`${element}`);
    if (emptyMessage) {
        const addTrackBtn = document.getElementById("form-add-track-btn");
        const container2 = document.getElementById(`${emptyMessage}`);
        container2.innerText = ""
        addTrackBtn.remove();
    }

    if (container.innerHTML == ""){
        const main = document.getElementById("update-form-btn");

            if (main){
                main.innerHTML = "";
            }

            const input = document.createElement("input");
            const addBtn = document.createElement("button");
            
            input.type = "text";
            input.name = "new_track";
            input.placeholder = "Enter track title";
            input.className = "form-field";
            input.required = true;
            
            addBtn.className = "remove-track submit-track";
            addBtn.innerHTML = " Submit ";
            addBtn.type = "submit";
            addBtn.name = "add-track";
            
            container.appendChild(input);
            container.appendChild(addBtn);
        }
        container.innerHTML += `<div class="cancel-btn-container"><button class='cancel-btn type='button' id='cancel-btn' onclick='cancel()'>
                                Cancel
                                </button></div>`
}


function removeTrack(field,btn) {
    const fieldelement = document.getElementById(`${field}`);
    const btnelement = document.getElementById(`${btn}`);
    btnelement.remove()
    fieldelement.remove()
    

}

function addTrack2() {
    const current= Math.floor(Math.random() * (1000 - 1 + 1)) + 1

    const container = document.getElementById("newTrack");
    const input = document.createElement("input");
    const addBtn = document.createElement("button");
    
    input.type = "text";
    input.name = `newTrack-${current}`;
    input.placeholder = "Enter track title";
    input.className = "form-field add-track-field";
    input.id = `newTrack-${current}`;
    input.required = true;

    
    addBtn.className = "remove-track submit-track";
    addBtn.innerHTML = " Cancel ";
    addBtn.type = "button";
    addBtn.name = "add-track";
    addBtn.id = `btn-${current}`;
    addBtn.addEventListener("click", () => {
        removeTrack(`newTrack-${current}`, `btn-${current}`);
    });
    
    container.appendChild(input);
    container.appendChild(addBtn);

}




function cancel() {
    const main = document.getElementById("update-form-btn")
    const newTrack = document.getElementById("new_track");
    const emptyMessage = document.getElementById("message-empty");
    const form = document.getElementById("update-form");
    const cancelBtn = document.getElementById("cancel-btn");

    cancelBtn.remove();

    newTrack.innerHTML = "";
    if (!emptyMessage){

        main.innerHTML = `<button type='submit' name='update_album' class='form-update' id='form-update-btn'>
                            Update
                        </button>
                        <button type='button' name='add-track' class='form-add-track' id='form-add-track-btn' 
                        onclick="addTrack('new_track')">
                        Add track
                        </button>`;
    } else {
        emptyMessage.innerText = "Album has no track added yet....";
        form.innerHTML += `<button type="button" name="add-track" class="form-add-track form-empty"
                            id="form-add-track-btn" onclick="addTrack('new_track', 'message-empty')">
                            Add track
                            </button>`
    }
}

function displayField() {
    const container = document.getElementById("album-title-field");
    container.classList.remove('hide-form')

}
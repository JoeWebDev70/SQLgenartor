// copy text in script div
function copyToClipboard() {
    const sqlScriptDiv = document.querySelector("#sqlScript");
    const textToCopy = sqlScriptDiv.innerText; //get text
    
    // copy txt on clipboard
    navigator.clipboard.writeText(textToCopy) 
    .then(function() { 
        alert("Texte copié dans le presse-papiers");
    }).catch(function(error) {
        alert("Erreur lors de la copie dans le presse-papiers : " + error);
    });
}

//get copy button and add eventbutton
const copyButton = document.querySelector("#copyButton");
if(copyButton != null){
    copyButton.addEventListener("click", copyToClipboard); 
}

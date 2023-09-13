const fileInput = document.getElementById("art_form_artfile");
const image = document.getElementById("image");
const form = document.getElementById("loading");
const message = document.getElementById("message");
const spinner = document.getElementById("spinner");
const uploadSubmit = document.getElementById("art_form_submit");
let model;


/**
 * Get the image from file input and display on page
 */
function getImage() {
    // Check if an image has been found in the input
    if (!fileInput.files[0]) throw new Error("Image not found");
    const file = fileInput.files[0];

    // Get the data url form the image
    const reader = new FileReader();

    // When reader is ready display image
    reader.onload = function (event) {
        const dataUrl = event.target.result;


        // create image object
        const imageElement = new Image();
        imageElement.src = dataUrl;

        // When image object is loaded
        imageElement.onload = function () {
            // Set <img /> attributes

            image.setAttribute("src", this.src);
            image.setAttribute("height", "auto");
            image.setAttribute("width", "200px");

            // Classify image
            classifyImage();
        };






    };

    // Get data URL
    reader.readAsDataURL(file);
}

/**
 * Load model
 */
nsfwjs.load('./../www/nsfw_model/', { type: 'graph' }).then(function (m) {
    //Save model
    model = m

    // Remove loading class from body
    spinner.classList.remove("spinner-border")
    form.classList.remove("loading");



    // When user uploads a new image, display the new image on the webpage
    fileInput.addEventListener("change", getImage);

    //console.log(model);
});



function classifyImage() {
    model.classify(image).then(function (predictions) {


        handleClassification(predictions);



        console.log("Predictions: ");
        console.log(predictions);
    });
}

function handleClassification(predictions) {

    let prediction = predictions[0]['className']
    if(prediction === 'Neutral'){
        uploadSubmit.disabled = false;   //enable upload button if image meets guidelines
        image.classList.remove("blur");
        message.classList.remove("text-danger");
        message.classList.add("text-success");
        message.innerText = "Nice Image! Click upload button to proceed...";
    }
    if(prediction === 'Drawing'){
        uploadSubmit.disabled = false;   //enable upload button if image meets guidelines
        image.classList.remove("blur");
        message.classList.remove("text-danger");
        message.classList.add("text-success");
        message.innerText = "Nice Image! Click upload button to proceed...";


    }
    if(prediction === 'Porn'){
        uploadSubmit.disabled = true;   //disable upload button if image is explicit
        image.classList.add("blur");
        message.classList.remove("text-success");
        message.classList.add("text-danger");
        message.innerText = "The image you selected was flagged for violating our content guidelines, please choose another image.";


    }
    if(prediction === 'Hentai'){
        uploadSubmit.disabled = true;   //disable upload button if image is explicit
        image.classList.add("blur");
        message.classList.remove("text-success");
        message.classList.add("text-danger");
        message.innerText = "The image you selected was flagged for violating our content guidelines, please choose another image.";


    }
    if(prediction === 'Sexy'){
        uploadSubmit.disabled = true;   //disable upload button if image is explicit
        image.classList.add("blur");
        message.classList.remove(("text-success"));
        message.classList.add(("text-danger"));
        message.innerText = "The image you selected was flagged for violating our content guidelines, please choose another image.";


    }








}


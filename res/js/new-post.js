const form = document.getElementById("form")
const picture_input = document.getElementById("picture")
const picture = document.getElementById("img")
const rotate_input = document.getElementById("rotate")
const descriptionInput = document.getElementsByName("description")[0]
let rotate = 0

const toolbarOptions = [
  ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
  ['blockquote', 'code-block'],
  ['link'],

  [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'list': 'check' }],
  [{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript
  [{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent
  [{ 'direction': 'rtl' }],                         // text direction

  [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
  [{ 'header': [1, 2, 3, 4, 5, 6, false] }],

  [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
  [{ 'font': [] }],
  [{ 'align': [] }],

  ['clean']                                         // remove formatting button
];

const quill = new Quill('#description', {
  modules: {
    toolbar: toolbarOptions
  },
  theme: 'snow'
});

form.addEventListener('submit', function(event) {
    event.preventDefault();

    descriptionInput.value = quill.root.innerHTML;
    rotate_input.value = rotate;
    
    form.submit();
})

picture_input.addEventListener('change', function(event) {
  console.log(picture_input.files[0]["name"]);
  const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            picture.src = e.target.result;
        };
        reader.readAsDataURL(file);
  }
})

function turn(angle) {
  rotate += angle
  rotate %= 360
  picture.style.transform = `rotate(${rotate}deg)`
}
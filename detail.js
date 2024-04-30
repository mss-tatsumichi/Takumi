// function delete_check() {
//     var dialog = document.getElementById('dialog');
//     dialog.style.display = "block";
//     document.getElementById('ok').addEventListener('click', () => {
//         return true;
//     });
//     document.getElementById('no').addEventListener('click', () => {
//         dialog.style.display = "none";
//         return false;
//     });
// }

function delete_check(){
    return confirm('本当に削除しますか');
}
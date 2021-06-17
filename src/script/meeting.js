const meetingMoveOptions = document.querySelectorAll('#meetingID option');
const contents = document.querySelectorAll('.content');
const materiMateri = document.querySelectorAll('#materi article');
const submitTaskBtn = document.getElementById('submitTask');


// Berpindah ke pertemuan lain di kelas yang sama
meetingMoveOptions.forEach((option) => {
  option.addEventListener('click', () => {
    window.location.href = option.getAttribute('data-link');
  })
});


// Menambahkan element <hr> diantara materi-materi yang ada
materiMateri.forEach((materi) => {
  const lastIndex = materiMateri.length-1;

  if (materi !== materiMateri.item(lastIndex)) {
    materi.after(document.createElement('hr'));
  }
})

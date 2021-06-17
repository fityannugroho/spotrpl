const MKrecords = document.querySelectorAll('tbody > tr');

MKrecords.forEach((record) => {
  record.addEventListener('click', () => {
    const detailMKLinkPage = record.getAttribute('data-link');
    window.open(detailMKLinkPage, '_self');
  });
});

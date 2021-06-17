const burgerIcon = document.querySelector('.burger-icon');
const mainMenus = document.querySelector('.main-menus');
const menus = document.querySelectorAll('.main-menus li');
const profileBox = document.querySelector('.profile-box');
const profileToggle = document.querySelector('#profileToggle');

const navSlide = () => {
    mainMenus.classList.toggle('active');

    menus.forEach((menu, index) => {
        if (menu.style.animation) {
            menu.style.animation = '';
        } else {
            menu.style.animation = `mainMenusFade 0.5s ease forwards ${index / 7 + 0.5}s`;
        }
    });

    if (burgerIcon.firstChild.innerHTML === 'menu') {
        burgerIcon.firstChild.innerHTML = 'close';
    } else {
        burgerIcon.firstChild.innerHTML = 'menu';
    }
}

const displayProfile = () => {
    profileBox.classList.toggle('active');
    profileToggleIcon = profileToggle.querySelector('.material-icons-outlined');

    if (profileToggleIcon.innerHTML === 'arrow_drop_down') {
        profileToggleIcon.innerHTML = 'arrow_drop_up';
    } else {
        profileToggleIcon.innerHTML = 'arrow_drop_down';
    }

}

burgerIcon.addEventListener('click', () => {
    navSlide();
    if (profileBox.classList.contains('active')) {
        displayProfile();
    }
});

profileToggle.addEventListener('click', () => {
    displayProfile();

    if (mainMenus.classList.contains('active')) {
        navSlide();
    }
});

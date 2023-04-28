/*=========================================================================================
    File Name: app-email.js
    Description: Email Page js
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy  - Vuejs, HTML & Laravel Admin Dashboard Template
    Author: PIXINVENT
    Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

'use strict';

$(function () {
  // Register Quill Fonts
  var Font = Quill.import('formats/font');
  Font.whitelist = ['sofia', 'slabo', 'roboto', 'inconsolata', 'ubuntu'];
  Quill.register(Font, true);

  var 
    sidebarToggle = $('.sidebar-toggle'),
    sidebarLeft = $('.sidebar-left'),
    sidebarMenuList = $('.sidebar-menu-list'),
    emailAppList = $('.email-app-list'),
    emailUserList = $('.email-user-list'),
    emailUserListInput = $('.email-user-list .custom-checkbox'),
    emailScrollArea = $('.email-scroll-area'),
    emailDetails = $('.email-app-details'),
    listGroupMsg = $('.list-group-messages'),
    listGroupItem = $('.my-app-class'),
    goBack = $('.go-back'),
    favoriteStar = $('.email-application .email-favorite'),
    userActions = $('.user-action'),
    mailDelete = $('#notification-delete'),
    emailSearch = $('#email-search'),
    mailUnreadChecked = $('.mail-unread-checked'),
    mailReadChecked = $('.mail-read-checked'),
    select_All = $('#select_All'),
    select_Unread = $('#select_Unread'),
    select_Read = $('#select_Read'),
    select_Starred = $('#select_Starred'),
    favoriteIcon_details = $('#favoriteIcon_details'),
    unreadIcon_details = $('#unreadIcon_details'),
    deleteIcon_details = $('#deleteIcon_details'),
    overlay = $('.body-content-overlay'),
    isRtl = $('html').attr('data-textdirection') === 'rtl';

  var assetPath = '../../../app-assets/';

  if ($('body').attr('data-framework') === 'laravel') {
    assetPath = $('body').attr('data-asset-path');
  }


  // if it is not touch device
  if (!$.app.menu.is_touch_device()) {
    // Email left Sidebar
    if ($(sidebarMenuList).length > 0) {
      var sidebar_menu_list = new PerfectScrollbar(sidebarMenuList[0]);
    }

    // User list scroll
    if ($(emailUserList).length > 0) {
      var users_list = new PerfectScrollbar(emailUserList[0]);
    }

    // Email detail section
    if ($(emailScrollArea).length > 0) {
      var users_list = new PerfectScrollbar(emailScrollArea[0]);
    }
  }
  // if it is a touch device
  else {
    $(sidebarMenuList).css('overflow', 'scroll');
    $(emailUserList).css('overflow', 'scroll');
    $(emailScrollArea).css('overflow', 'scroll');
  }



  
  // Email sidebar toggle
  if (sidebarToggle.length) {
    sidebarToggle.on('click', function (e) {
      e.stopPropagation();
      sidebarLeft.toggleClass('show');
      overlay.addClass('show');
    });
  }

  // Overlay Click
  if (overlay.length) {
    overlay.on('click', function (e) {
      sidebarLeft.removeClass('show');
      overlay.removeClass('show');
    });
  }

  // AH- fav in details
  if (favoriteIcon_details.length) {
    favoriteIcon_details.on('click', function () {
      var myClass = favoriteIcon_details[0].className;
      var mynotificationID = document.getElementById("notificationID").innerHTML;// AH- get the id of the notification
      var myLiElement = document.getElementById(mynotificationID);
      // console.log(myLiElement.parentElement.nextElementSibling.lastChild);
      if (myClass == 'fas fa-star') {
        let request = sendFavRequest(mynotificationID, 0);
        request.done(() => {
          favoriteIcon_details[0].style.color = "";
          favoriteIcon_details[0].className = 'far fa-star';
          document.getElementById("count_notif_fav").innerHTML = parseInt(document.getElementById("count_notif_fav").innerHTML) - 1;
          myLiElement.parentElement.nextElementSibling.lastChild.className["baseVal"] = 'feather feather-star';
          toastr['error'](errorFavorite, 'Favorite Notification ⭐️', {
            closeButton: true,
            tapToDismiss: false,
            rtl: isRtl
          });
        });
      }else{
        let request = sendFavRequest(mynotificationID, 1);
        request.done(() => {
          favoriteIcon_details[0].className = 'fas fa-star';
          favoriteIcon_details[0].style.color = "orange";
          document.getElementById("count_notif_fav").innerHTML = parseInt(document.getElementById("count_notif_fav").innerHTML) + 1;
          myLiElement.parentElement.nextElementSibling.lastChild.className["baseVal"] = 'feather feather-star favorite';
          toastr['success'](successFavorite, 'Favorite Notification ⭐️', {
            closeButton: true,
            tapToDismiss: false,
            rtl: isRtl
          });
        });
      }

    });
  
  }

  // AH- unread in details
  if (unreadIcon_details.length) {
    unreadIcon_details.on('click', function (e) {
      var mynotificationID = document.getElementById("notificationID").innerHTML;// AH- get the id of the notification
      var myLiElement = document.getElementById(mynotificationID).parentElement.parentElement.parentElement.parentElement;// AH- get the <li>
      //console.log(myLiElement);
      let request = sendunMarkRequest(mynotificationID);
      request.done(() => {
        //AH- increase number of notif_count, uncheck the selected notif and add mail-read class to the <li>
        document.getElementById("count_notif_unread").innerHTML = parseInt(document.getElementById("count_notif_unread").innerHTML) + 1;
        myLiElement.className ='media mail-read';// AH- change the class of the <li> to looks unread
        e.stopPropagation();
        emailDetails.removeClass('show');// AH- return to the notification list
      });
    });
  }

  // AH- delete details
  if (deleteIcon_details.length) {
    deleteIcon_details.on('click', function (e) {

      var mynotificationID = document.getElementById("notificationID").innerHTML;// AH- get the id of the notification
      var myLiElement = document.getElementById(mynotificationID);
      //console.log(myLiElement);
      let request = sendDeleteRequest(mynotificationID);
      request.done(() => {
        //console.log(myLiElement.parentElement.nextElementSibling.lastChild.className["baseVal"]);
        if (myLiElement.parentElement.nextElementSibling.lastChild.className["baseVal"] == "feather feather-star favorite") {//AH- check if it's a favorite
          document.getElementById("count_notif_fav").innerHTML = parseInt(document.getElementById("count_notif_fav").innerHTML) - 1;
        }
        myLiElement.parentElement.parentElement.parentElement.parentElement.remove();

        e.stopPropagation();
        emailDetails.removeClass('show');
      });
    });
  
  }

  // AH- "details"
  if (emailUserList.find('li').length) {
    emailUserList.find('li').on('click', function (e) {
      //AH- get the id from the span
      var myNotificationID = $(this)[0].childNodes[1].lastElementChild.lastElementChild.getAttribute('data-id');
      //console.log(myNotificationID);
      let request = getNotificationByID(myNotificationID);
        request.done(() => {
          //AH- on api done, get the notification details
          var myNotificationDetails = request.responseJSON;
          var myLiElement = document.getElementById(myNotificationID).parentElement.parentElement.parentElement.parentElement;// AH - get the <li> element
          if (myLiElement.className != 'media') {// AH- check if it's already read or not
            let request = sendMarkRequest(myNotificationID);
            request.done(() => {
              document.getElementById("count_notif_unread").innerHTML = document.getElementById("count_notif_unread").innerHTML - 1;          
              myLiElement.className ='media';
            });
          }
          if(myNotificationDetails.is_favorites == 1){
            favoriteIcon_details[0].className = 'fas fa-star';
            favoriteIcon_details[0].style.color = "orange";
          }else{
            favoriteIcon_details[0].className = 'far fa-star';
          }
          // AH- start filling the details div with our DATA
          // console.log( document.getElementById("applicationTitle").getAttribute('href'));

          // document.getElementById("dropdownMenuButton200").innerHTML = myNotificationID;
          // document.getElementById("notification_from").innerHTML = myNotificationDetails.data['app'];
          // document.getElementById("notification_to").innerHTML = myNotificationDetails.data['app'];
          // document.getElementById("notification_at").innerHTML = myNotificationDetails.created_at;

          var createdAT = new Date(myNotificationDetails.created_at);
          var year = createdAT.getFullYear();
          var month = createdAT.getMonth()+1;
          var day = createdAT.getDate();
          var hour = createdAT.getHours();
          var minute = createdAT.getMinutes();
          var seconde = createdAT.getSeconds();

          document.getElementById("notificationID").innerHTML = myNotificationID;
          document.getElementById("project_name").innerHTML = myNotificationDetails.data['app'];
          document.getElementById("applicationTitle").innerHTML = myNotificationDetails.data['subject'];
          document.getElementById("created_at").innerHTML = year + "-" + month +"-"  + day +" " + hour+ ":"+minute+":"+seconde;
          document.getElementById("notification_description").innerHTML = myNotificationDetails.data['summary'];
          //AH- TO DO : data['sender] = id of the sender, need to get the name
          document.getElementById("notification_end").innerHTML = "Sent by " + myNotificationDetails.data['sender'] /* senderName */ ;
          document.getElementById("applicationTitle").href = myNotificationDetails.data['link'];

          //AH - show the details div
          emailDetails.toggleClass('show');
        });
    });
  }

  // Email detail view back button click
  if (goBack.length) {
    goBack.on('click', function (e) {
      e.stopPropagation();
      emailDetails.removeClass('show');
    });
  }

  // Add class active on click of sidebar list
  if (listGroupMsg.find('a').length) {
    listGroupMsg.find('a').on('click', function () {
      if (listGroupMsg.find('a').hasClass('active')) {
        listGroupMsg.find('a').removeClass('active');
      }
      $(this).addClass('active');
    });
  }

  // For app sidebar on small screen
  if ($(window).width() > 768) {
    if (overlay.hasClass('show')) {
      overlay.removeClass('show');
    }
  }

  // single checkbox select
  if (emailUserListInput.length) {
    emailUserListInput.on('click', function (e) {
      e.stopPropagation();
    });
    emailUserListInput.find('input').on('change', function (e) {
      e.stopPropagation();
      var $this = $(this);
      if ($this.is(':checked')) {
        $this.closest('.media').addClass('selected-row-bg');
      } else {
        $this.closest('.media').removeClass('selected-row-bg');
      }
    });
  }

  // select all
  $(document).on('click', '.email-app-list .selectAll input', function () {
    if ($(this).is(':checked')) {
      userActions
        .find('.custom-checkbox input')
        .prop('checked', this.checked)
        .closest('.media')
        .addClass('selected-row-bg');
    } else {
      userActions.find('.custom-checkbox input').prop('checked', '').closest('.media').removeClass('selected-row-bg');
    }
  });

  // AH- Favorite star click
  if (favoriteStar.length) {
    favoriteStar.on('click', function (e) {
      e.stopPropagation();
      if ($(this).find('svg').hasClass('favorite')) { //AH- if notification already in favorites
        //AH- call api to remove notification from fav list
        let request = sendFavRequest(e["currentTarget"].getAttribute('data-id'), 0);
        request.done(() => {
          // console.log($(this));
          //AH- on api done, remove from favorite, decrease the fav_count and display toast
          $(this).find('svg').removeClass('favorite');
          document.getElementById("count_notif_fav").innerHTML = parseInt(document.getElementById("count_notif_fav").innerHTML) - 1;
          toastr['error'](errorFavorite, 'Favorite Notification ⭐️', {
            closeButton: true,
            tapToDismiss: false,
            rtl: isRtl
          });
        });
      }else{ //AH- if notification is not in favorites
        //AH- call api to add notification to fav list
        let request = sendFavRequest(e["currentTarget"].getAttribute('data-id'), 1);
        request.done(() => {
          // console.log($(this));
          $(this).find('svg').toggleClass('favorite');
          document.getElementById("count_notif_fav").innerHTML = parseInt(document.getElementById("count_notif_fav").innerHTML) + 1;
          toastr['success'](successFavorite, 'Favorite Notification ⭐️', {
            closeButton: true,
            tapToDismiss: false,
            rtl: isRtl
          });
        });
      }
    });
  }

  // AH- Delete selected Mail from list
  if (mailDelete.length) {
    mailDelete.on('click', function () {
      var myarray = userActions.find('.custom-checkbox input:checked');
      if (myarray.length) {// AH- check if at least one notif is selected
        var tableIDS = [];
        var count_notif_unread = parseInt(document.getElementById("count_notif_unread").innerHTML);
        var count_notif_fav = parseInt(document.getElementById("count_notif_fav").innerHTML);
        Object.keys(myarray).forEach(key => {
          if(myarray[key].id ){// AH- if the selected notif has id
            tableIDS.push(myarray[key].id); 
            var myClassName = document.getElementById(myarray[key].id).parentElement.parentElement.parentElement.parentElement.className;
            if(myClassName.includes("mail-read")){
              count_notif_unread = count_notif_unread -1;
            }
            if(document.getElementById(myarray[key].id).parentElement.nextElementSibling.lastElementChild.className["baseVal"].includes("favorite")){
              count_notif_fav = count_notif_fav -1; 
            }
            document.getElementById(myarray[key].id).parentElement.parentElement.parentElement.parentElement.remove();
          }
        });
        let request = sendMultipleDeleteRequestq(tableIDS);
        request.done(() => {
          document.getElementById("count_notif_unread").innerHTML = count_notif_unread;
          document.getElementById("count_notif_fav").innerHTML = count_notif_fav;
          $('input[type="checkbox"]').each(function() {
            this.checked = false;
          });
        });
        //AH- display 1 toast after finish deleting all the selected notif
        toastr['error'](removeNotification, 'Notification Deleted!', {
          closeButton: true,
          tapToDismiss: false,
          rtl: isRtl
        });
        //AH- unselect notitifcation if something wrong happen
        emailUserList.find('.email-media-list li').removeClass('selected-row-bg');
      }
    });
  }

  // AH- Mark selected Mail read from list
  if (mailReadChecked.length) {
    mailReadChecked.on('click', function () {
      var myarray = userActions.find('.custom-checkbox input:checked');
      if (myarray.length) {// AH- at least one notif selected
        var tableIDS = [];
        var count_notif_unread = parseInt(document.getElementById("count_notif_unread").innerHTML);
        Object.keys(myarray).forEach(key => {// AH- go throw the selected notif one by one
          if(myarray[key].id){// AH- if notif has id
            // AH- get the <input> element by ID THEN get the className of the <li> belongs to
            var myClassName = document.getElementById(myarray[key].id).parentElement.parentElement.parentElement.parentElement.className;
            if(myClassName.includes("mail-read")){
              tableIDS.push(myarray[key].id);            
              //AH- decrease number of notif_count, uncheck the selected notif and remove mail-read class from the <li>
              count_notif_unread = count_notif_unread - 1;
              document.getElementById(myarray[key].id).parentElement.parentElement.parentElement.parentElement.classList.remove("mail-read");
              // userActions.find('.custom-checkbox input:checked').closest('.media').removeClass('mail-read');
            }
          }
        });
        let request = sendMultipleMarkRequest(tableIDS);
        request.done(() => {
          document.getElementById("count_notif_unread").innerHTML = count_notif_unread;
          $('input[type="checkbox"]').each(function() {
            this.checked = false;
          });
        });
        toastr['success'](readNotification, 'Notification read!', {
          closeButton: true,
          tapToDismiss: false,
          rtl: isRtl
        });
        emailUserList.find('.email-media-list li').removeClass('selected-row-bg');
      }
    });
  }

  //AH- Mark selected Mail unread from list
  if (mailUnreadChecked.length) {
    mailUnreadChecked.on('click', function () {
      var myarray = userActions.find('.custom-checkbox input:checked');
      if (myarray.length) {// AH- at least one notif selected
        var tableIDS = [];
        var count_notif_unread = parseInt(document.getElementById("count_notif_unread").innerHTML);
        Object.keys(myarray).forEach(key => {// AH- go throw the selected notif one by one
          if(myarray[key].id){// AH- if notif has id
            // AH- get the <input> element by ID THEN get the className of the <li> belongs to
            var myClassName = document.getElementById(myarray[key].id).parentElement.parentElement.parentElement.parentElement.className;
            if(myClassName == "media" || myClassName == "media selected-row-bg"){// AH- classname can be media if only one notif selected or  media selected-row-bg if multiple notif selected
              tableIDS.push(myarray[key].id);  
              //AH- increase number of notif_count, uncheck the selected notif and add mail-read class to the <li>          
              count_notif_unread = count_notif_unread + 1;
              document.getElementById(myarray[key].id).parentElement.parentElement.parentElement.parentElement.classList.add("mail-read");
              // userActions.find('.custom-checkbox input:checked').closest('.media').addClass('mail-read');
              // emailAppList.find('.selectAll input').prop('checked', false);
              // userActions.find('.custom-checkbox input').prop('checked', '');
            }
          }
        });
        let request = sendunReadMultipleRequests(tableIDS);
        request.done(() => {
          document.getElementById("count_notif_unread").innerHTML = count_notif_unread;
          $('input[type="checkbox"]').each(function() {
            this.checked = false;
          });
        });
        toastr['error'](Unreadnotifications, 'Notification unread!', {
          closeButton: true,
          tapToDismiss: false,
          rtl: isRtl
        });
        emailUserList.find('.email-media-list li').removeClass('selected-row-bg');
      } 
    });
  }

  // AH- Filter select_Read
  if (select_Read.length) {
    select_Read.on('click', function () {

      var value = 'class="media"';// AH- read notification = has class="media" in the html code
      emailUserList.find('.email-media-list li').filter(function () {//AH- go throught all the notitifcation 
        $(this).toggle($(this).get(0).outerHTML.indexOf(value) > -1);//AH- toggle the notif that have the "value" and make them visible
      });
      var tbl_row = emailUserList.find('.email-media-list li:visible').length;

      //Check if table has row or not
      if (tbl_row == 0) {//AH- if no result found, display no-results div
        emailUserList.find('.no-results').addClass('show');
        emailUserList.animate({ scrollTop: '0' }, 500);
      } else {//AH- make sure the no-result div is hiden
        if (emailUserList.find('.no-results').hasClass('show')) {
          emailUserList.find('.no-results').removeClass('show');
        }
      }

    });
  
  }

  // AH- Filter select_Starred
  if (select_Starred.length) {
    select_Starred.on('click', function () {

      var value = 'class="feather feather-star favorite"'; //AH- fav notification = has 'class="feather feather-star favorite' in the html code
      emailUserList.find('.email-media-list li').filter(function () {
        $(this).toggle($(this).get(0).outerHTML.indexOf(value) > -1);
      });
      var tbl_row = emailUserList.find('.email-media-list li:visible').length;

      if (tbl_row == 0) {
        emailUserList.find('.no-results').addClass('show');
        emailUserList.animate({ scrollTop: '0' }, 500);
      } else {
        if (emailUserList.find('.no-results').hasClass('show')) {
          emailUserList.find('.no-results').removeClass('show');
        }
      }

    });
  
  }
  

  // AH- Filter select_Unread
  if (select_Unread.length) {
    select_Unread.on('click', function () {

      var value = 'class="media mail-read"'; // AH- unread notification = has class="media mail-read in the html code
      emailUserList.find('.email-media-list li').filter(function () {
        $(this).toggle($(this).get(0).outerHTML.indexOf(value) > -1);
      });
      var tbl_row = emailUserList.find('.email-media-list li:visible').length;

      if (tbl_row == 0) {
        emailUserList.find('.no-results').addClass('show');
        emailUserList.animate({ scrollTop: '0' }, 500);
      } else {
        if (emailUserList.find('.no-results').hasClass('show')) {
          emailUserList.find('.no-results').removeClass('show');
        }
      }

    });
  
  }

  // AH- Filter select_All
  if (select_All.length) {
    select_All.on('click', function () {

      var value = 'class="media'; // AH- all notification has media in their <li>
      emailUserList.find('.email-media-list li').filter(function () {
        $(this).toggle($(this).get(0).outerHTML.indexOf(value) > -1);
      });
      var tbl_row = emailUserList.find('.email-media-list li:visible').length;

      if (tbl_row == 0) {
        emailUserList.find('.no-results').addClass('show');
        emailUserList.animate({ scrollTop: '0' }, 500);
      } else {
        if (emailUserList.find('.no-results').hasClass('show')) {
          emailUserList.find('.no-results').removeClass('show');
        }
      }

    });
  
  }

  // AH- Filter select App
  if (listGroupItem.length) {
    listGroupItem.on('click', function () {

      var value = $(this)[0].lastElementChild.className;
      emailUserList.find('.email-media-list li').filter(function () {
        $(this).toggle($(this).get(0).outerHTML.indexOf(value) > -1);
      });
      var tbl_row = emailUserList.find('.email-media-list li:visible').length;

      if (tbl_row == 0) {
        emailUserList.find('.no-results').addClass('show');
        emailUserList.animate({ scrollTop: '0' }, 500);
      } else {
        if (emailUserList.find('.no-results').hasClass('show')) {
          emailUserList.find('.no-results').removeClass('show');
        }
      }

    });
  }


  // Filter
  if (emailSearch.length) {
    emailSearch.on('keyup', function () {
      var value = $(this).val().toLowerCase();
      if (value !== '') {
        emailUserList.find('.email-media-list li').filter(function () {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
        var tbl_row = emailUserList.find('.email-media-list li:visible').length;

        //Check if table has row or not
        if (tbl_row == 0) {
          emailUserList.find('.no-results').addClass('show');
          emailUserList.animate({ scrollTop: '0' }, 500);
        } else {
          if (emailUserList.find('.no-results').hasClass('show')) {
            emailUserList.find('.no-results').removeClass('show');
          }
        }
      } else {
        // If filter box is empty
        emailUserList.find('.email-media-list li').show();
        if (emailUserList.find('.no-results').hasClass('show')) {
          emailUserList.find('.no-results').removeClass('show');
        }
      }
    });
  }

});

$(window).on('resize', function () {
  var sidebarLeft = $('.sidebar-left');
  // remove show classes from sidebar and overlay if size is > 992
  if ($(window).width() > 768) {
    if ($('.app-content .body-content-overlay').hasClass('show')) {
      sidebarLeft.removeClass('show');
      $('.app-content .body-content-overlay').removeClass('show');
    }
  }
});
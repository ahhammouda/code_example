@extends('layouts.masterLayout')

@section('title', 'Email Application')

@section('vendor-style')
   <!-- BEGIN: Vendor CSS-->
   <link rel="stylesheet" href="/myadmin/resources/themes/vuexy/vendors/css/vendors.min.css">
   <link rel="stylesheet" href="/myadmin/resources/themes/vuexy/vendors/css/editors/quill/katex.min.css">
   <link rel="stylesheet" href="/myadmin/resources/themes/vuexy/vendors/css/editors/quill/monokai-sublime.min.css">
   <link rel="stylesheet" href="/myadmin/resources/themes/vuexy/vendors/css/editors/quill/quill.snow.css">
   <link rel="stylesheet" href="/myadmin/resources/themes/vuexy/vendors/css/extensions/toastr.min.css">
   <link rel="stylesheet" href="/myadmin/resources/themes/vuexy/vendors/css/forms/select/select2.min.css">
@endsection
@section('page-style')
   <!-- Page css files -->
   <link rel="stylesheet" href="/myadmin/resources/themes/vuexy/css/plugins/forms/form-quill-editor.css">
   <link rel="stylesheet" href="/myadmin/resources/themes/vuexy/css/plugins/extensions/ext-component-toastr.css">
   <link rel="stylesheet" href="/myadmin/resources/themes/vuexy/css/pages/app-email.css">
@endsection
@section('content')
  <!-- BEGIN: Content-->
  <?php 
      //AH- get translation texts that will be used in app-notifications.js 
      $errorFavorite = trans('notification.errorFavorite');
      $successFavorite = trans('notification.successFavorite');
      $removeNotification = trans('notification.removeNotification');
      $readNotification =  trans('notification.readNotification');
      $Unreadnotifications =  trans('notification.Unreadnotifications');
   ?>
  <div class="email-application">
   <div class="content-overlay"></div>
   <div class="header-navbar-shadow"></div>
   <div class="content-area-wrapper">
      <div class="sidebar-left">
         <div class="sidebar">
            <div class="sidebar-content email-app-sidebar">
               <div class="email-app-menu">
                  <!-- Compose Button start -->
                  <div class="form-group-compose text-center compose-btn"></div>
                  <!-- Compose Button end -->
                  <div class="sidebar-menu-list">
                     <!--  -->
                     <div class="list-group list-group-messages">
                        <a id="select_All" href="javascript:void(0)" class="list-group-item list-group-item-action active">
                          <i data-feather="mail" class="font-medium-3 mr-50"></i>
                          <span class="align-middle"><?php echo trans('notification.all'); ?></span>
                        </a>
                        <a id="select_Unread" href="javascript:void(0)" class="list-group-item list-group-item-action">
                          <i data-feather="send" class="font-medium-3 mr-50"></i>
                          <span class="align-middle"><?php echo trans('notification.unread'); ?></span>
                          <span id="count_notif_unread" class="badge badge-light-primary badge-pill float-right">{{ $count_notif_unread }}</span>
                        </a>
                        <a id="select_Read" href="javascript:void(0)" class="list-group-item list-group-item-action">
                          <i data-feather="edit-2" class="font-medium-3 mr-50"></i>
                          <span class="align-middle"><?php echo trans('notification.read'); ?></span>
                        </a>
                        <a id="select_Starred" href="javascript:void(0)" class="list-group-item list-group-item-action">
                          <i data-feather="star" class="font-medium-3 mr-50"></i>
                          <span class="align-middle"><?php echo trans('notification.starred'); ?></span>
                          <span id="count_notif_fav" class="badge badge-light-warning badge-pill float-right">{{ $count_notif_fav }}</span>
                        </a>
                     </div>
                     <!-- <hr /> -->
                     <?php 
                        $apps = Auth::user()->getEnabledApps(Auth::user());
                        $apps = $apps->sortBy('sort');
                     ?>
                     <h6 class="section-label mt-3 mb-1 px-2"><?php echo trans('notification.APPLICATIONS'); ?></h6>
                     <div class="list-group list-group-labels">
                        @foreach($apps as $app)
                          <a href="javascript:void(0)" class="list-group-item list-group-item-action my-app-class"><i class="fal fa-<?php echo $app->class; ?>"></i> {{ $app->name }}</a>
                        @endforeach
                     </div>
                     <!--  -->
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="content-right">
         <div class="content-wrapper">
            <div class="content-header row">
            </div>
            <div class="content-body">
               <div class="body-content-overlay"></div>
               <!-- Notification list Area -->
               <div class="email-app-list">
                  <!-- Notification search starts -->
                  <div class="app-fixed-search d-flex align-items-center">
                     <div class="sidebar-toggle d-block d-lg-none ml-1">
                        <i data-feather="menu" class="font-medium-5"></i>
                     </div>
                     <div class="d-flex align-content-center justify-content-between w-100">
                        <div class="input-group input-group-merge">
                           <div class="input-group-prepend">
                              <span class="input-group-text"><i data-feather="search" class="text-muted"></i></span>
                           </div>
                           <input type="text" class="form-control" id="email-search" placeholder="<?php echo trans('notification.searchNotification'); ?>" aria-label="Search..." aria-describedby="email-search" />
                        </div>
                     </div>
                  </div>
                  <!-- Notification search ends -->
                  <!-- Notification actions starts -->
                  <div class="app-action">
                     <div class="action-left">
                        <div class="custom-control custom-checkbox selectAll">
                           <input type="checkbox" class="custom-control-input" id="selectAllCheck" />
                           <label class="custom-control-label font-weight-bolder pl-25" for="selectAllCheck"><?php echo trans('notification.selectAll'); ?></label>
                        </div>
                     </div>
                     <div class="action-right">
                        <ul class="list-inline m-0">
                           <li class="list-inline-item mail-read-checked">
                              <span class="action-icon" data-toggle="tooltip" data-original-title="Mark as read"><i class="far fa-envelope-open"></i></span>
                           </li>
                           <li class="list-inline-item mail-unread-checked">
                              <span class="action-icon" data-toggle="tooltip" data-original-title="Mark as unread"><i data-feather="mail" class="font-medium-2"></i></span>
                           </li>
                           <li class="list-inline-item mail-delete">
                              <span data-toggle="modal" data-target="#exampleModalCenter" class="action-icon"><i data-feather="trash-2" class="font-medium-2"></i></span>
                           </li>
                        </ul>
                     </div>
                  </div>
                  <!-- Notification actions ends -->
                  <!-- Notification list starts -->
                  <div id="myNotifications" class="email-user-list">
                     <ul id="myList" class="email-media-list">
                        @forelse($notifications as $notification)
                           @if($notification->read_at == NULL)
                           <li class="media mail-read">
                           @else
                           <li class="media">
                           @endif
                              <div class="media-left pr-50">
                                 @if (array_key_exists("type", $notification->data) && $notification->data['type']=='chat')
                                    <?php
                                       if(array_key_exists("appID", $notification->data)){
                                          $myIcon = $app->class;
                                       }else {
                                          $myIcon = null;
                                       }
                                    ?>
                                    @if(array_key_exists("sender", $notification->data))
                                       <?php
                                          $sender = App\Models\User::where('id',$notification->data["sender"])->first();
                                       ?>
                                       <div class="avatar">
                                          @if(file_exists('/myadmin/public/images/avatars/' .$sender->photo)) 
                                             <img src="/myadmin/public/images/avatars/{{ $sender->photo }}" alt="Generic placeholder image" />
                                          @else
                                             <img src="/myadmin/public/images/avatars/default.jpg" alt="Generic placeholder image" />
                                          @endif
                                       </div>
                                    @else
                                       <div class="avatar">
                                          <img src="/myadmin/public/images/avatars/default.jpg" alt="Generic placeholder image" />
                                       </div>
                                    @endif                    
                                 @else
                                    @if(array_key_exists("appID", $notification->data))
                                       <div class="avatar">
                                          <?php 
                                             $sender = App\Models\User::where('id',$notification->data["sender"])->first();
                                             $app = App\Models\Apps::where('id', $notification->data["appID"])->first();
                                             $myIcon = $app->class;
                                          ?>
                                          <i class="fal fa-<?php echo $app->class; ?> avatarIcon"></i>
                                       </div>
                                    @else
                                       <?php 
                                          $myIcon = null;
                                       ?>
                                       <div class="avatar">
                                          <i class="fal fa-bug"></i>
                                       </div>
                                    @endif
                                 @endif
                                 <div class="user-action">
                                    <div class="custom-control custom-checkbox">
                                       <input type="checkbox" class="custom-control-input" id="{{ $notification->id }}" />
                                       <label class="custom-control-label" for="{{ $notification->id }}"></label>
                                    </div>
                                    @if($notification->is_favorites == 1)
                                       <span class="email-favorite" data-id="{{ $notification->id }}"><i data-feather="star" class="favorite" ></i></span>
                                    @else
                                       <span class="email-favorite" data-id="{{ $notification->id }}"><i data-feather="star"></i></span>
                                    @endif
                                 </div>
                              </div>
                              <div class="media-body">
                                 <div class="mail-details">
                                    <div class="mail-items">
                                       <h5 class="mb-25">
                                          @if (array_key_exists("subject", $notification->data) )
                                             <?php echo mb_convert_encoding($notification->data["subject"], 'UTF-8', 'HTML-ENTITIES'); ?>
                                          @else
                                             <?php echo trans('notification.mssingData'); ?>
                                          @endif
                                       </h5>
                                       @if (array_key_exists("type", $notification->data) && $notification->data['type']=='chat')
                                          <span class="text-truncate"> 
                                             @isset($sender)
                                                {{ $sender->firstname }} {{ $sender->lastname }}
                                             @endisset
                                             @empty($sender)
                                                <?php echo trans('notification.mssingData'); ?>
                                             @endempty
                                          </span>
                                       @else
                                          <span class="text-truncate"> 
                                             @if (array_key_exists("app", $notification->data))
                                                {{ $notification->data["app"] }} 
                                             @else
                                                <?php echo trans('notification.mssingData'); ?>
                                             @endif
                                          </span>
                                       @endif
                                    </div>
                                    <div class="mail-meta-item">
                                       @if($myIcon)
                                          <i style="visibility: hidden" class="fal fa-<?php echo $myIcon; ?>"></i>
                                       @else
                                          <span style="visibility: hidden" class="mr-50 bullet bullet-danger bullet-sm"></span>
                                       @endif
                                       <span class="mail-date">{{ $notification->created_at }} </span>
                                    </div>
                                 </div>
                                 <div class="mail-message">
                                    <p class="mb-0 text-truncate">
                                    @if (array_key_exists("summary", $notification->data))
                                       {{ $notification->data["summary"] }}
                                    @else
                                       <?php echo trans('notification.mssingData'); ?>
                                    @endif
                                    </p>
                                 </div>
                              </div>
                           </li>
                     @if($loop->last)
                        </ul>
                        @endif
                     @empty
                     <div class="no-results">
                        <h5><?php echo trans('notification.noItemsFound'); ?></h5>
                     </div>
                     @endforelse
                  </div>
                  <!-- Notification list ends -->
               </div>
               <!--/ Notification list Area -->
               <!-- Detailed Email View -->
               <div class="email-app-details">
                  <!-- Detailed Email Header starts -->
                  <div class="email-detail-header">
                     <div class="email-header-left d-flex align-items-center">
                        <span class="go-back mr-1"><i data-feather="chevron-left" class="font-medium-4"></i></span>
                        <h4 id="project_name" class="email-subject mb-0"><?php echo trans('notification.notificationDetails'); ?></h4>
                     </div>
                     <div class="email-header-right ml-2 pl-1">
                        <ul class="list-inline m-0">
                           <li class="list-inline-item">
                              <span class="action-icon favorite"><i id="favoriteIcon_details" class="far fa-star" ></i></i></span>
                           </li>
                           <li class="list-inline-item mail-unread-checked">
                              <span id="unreadIcon_details" class="action-icon"><i data-feather="mail" class="font-medium-2"></i></span>
                           </li>
                           <li class="list-inline-item mail-delete">
                              <span data-toggle="modal" data-target="#exampleModalCenter2" class="action-icon"><i data-feather="trash-2" class="font-medium-2"></i></span>
                           </li>
                        </ul>
                     </div>
                  </div>
                  <!-- Detailed Email Header ends -->
                  <!-- Detailed Email Content starts -->
                  <div class="email-scroll-area">
                     <!-- notification ID -->
                     <div style="visibility: hidden" class="row">
                        <div class="col-12">
                           <div class="email-label">
                              <span id="notificationID" class="mail-label badge badge-pill badge-light-primary">notificationID</span>
                           </div>
                        </div>
                     </div>
                     <!-- notification body -->
                     <div class="row">
                        <div class="col-12">
                           <div class="card">
                              <div class="card-header email-detail-head">
                                 <div class="user-details d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mail-items">
                                       <a href="www.google.com" target="_blank" id="applicationTitle" class="mb-0 notificationLink">applicationTitle</a>
                                    </div>
                                 </div>
                                 <div class="mail-meta-item d-flex align-items-center">
                                    <small id="created_at" class="mail-date-time text-muted">17 May, 2020, 4:14</small>
                                 </div>
                              </div>
                              <div class="card-body mail-message-wrapper pt-2">
                                 <div class="mail-message">
                                    <p id="notification_description" class="card-text">
                                       bah kivu decrete epanorthotic unnotched Argyroneta nonius veratrine preimaginary saunders demidolmen
                                       Chaldaic allusiveness lorriker unworshipping ribaldish tableman hendiadys outwrest unendeavored
                                       fulfillment scientifical Pianokoto Chelonia
                                    </p>
                                 </div>
                              </div>
                              <div class="card-footer">
                                 <div class="mail-attachments">
                                    <div class="d-flex align-items-center mb-1">
                                       <h5 id="notification_end" class="font-weight-bolder text-body mb-0">2021-04-23 01:45:35, created by Jerome Dreisbach .</h5>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <!-- Detailed Email Content ends -->
               </div>
               <!--/ Detailed Email View -->
            </div>
         </div>
      </div>
   </div>
</div>
<!-- END: Content-->



<!-- Modal -->
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered" role="document">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="exampleModalCenterTitle"><?php echo trans('notification.Delete_Question'); ?></h5>
               
            </div>
            <div class="modal-body">
               <p>
               <?php echo trans('notification.Delete_Description'); ?>
               </p>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-primary" data-dismiss="modal"> <?php echo trans('notification.Delete_NO'); ?> </button>
               <button id="notification-delete" type="button" class="btn btn-primary" data-dismiss="modal"><?php echo trans('notification.Delete_YES'); ?></button>
            </div>
         </div>
   </div>
</div>
<!-- END modal -->

<!-- Modal for Details-->
<div class="modal fade" id="exampleModalCenter2" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered" role="document">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="exampleModalCenterTitle"><?php echo trans('notification.Delete_Question'); ?></h5>
               
            </div>
            <div class="modal-body">
               <p>
               <?php echo trans('notification.Delete_Description'); ?>
               </p>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-primary" data-dismiss="modal"> <?php echo trans('notification.Delete_NO'); ?> </button>
               <button id="deleteIcon_details" type="button" class="btn btn-primary" data-dismiss="modal"><?php echo trans('notification.Delete_YES'); ?></button>
            </div>
         </div>
   </div>
</div>
<!-- END modal -->
@endsection

@section('vendor-script')
<!-- vendor js files -->
      <script src="/myadmin/resources/themes/vuexy/vendors/js/editors/quill/katex.min.js"></script>
      <script src="/myadmin/resources/themes/vuexy/vendors/js/editors/quill/highlight.min.js"></script>
      <script src="/myadmin/resources/themes/vuexy/vendors/js/editors/quill/quill.min.js"></script>
      <script src="/myadmin/resources/themes/vuexy/vendors/js/extensions/toastr.min.js"></script>
      <script src="/myadmin/resources/themes/vuexy/vendors/js/forms/select/select2.full.min.js"></script>
@endsection
@section('page-script')
  <!-- Page js files -->
   <script>
      //AH - store translated texts in js variabls
      var errorFavorite = "<?php echo $errorFavorite; ?>"; 
      var successFavorite = "<?php echo $successFavorite; ?>"; 
      var removeNotification = "<?php echo $removeNotification; ?>"; 
      var readNotification = "<?php echo $readNotification; ?>"; 
      var Unreadnotifications = "<?php echo $Unreadnotifications; ?>"; 
      // var senderName = "<?php //echo $sender->firstname . ' ' . $sender->lastname; ?>"; 
   </script>
  <script src="/myadmin/resources/themes/vuexy/js/scripts/pages/app-notifications.js"></script>
  <script>

   function sendMarkRequest(id = null) {
      return $.ajax({
         url: '/myAppsForce/mark-as-read', 
         method: 'POST',
         data: {
               id
         }
      });
   }

   function sendFavRequest(id = null, state = null) {
      return $.ajax({
         url: '/myAppsForce/mark-as-favorites', 
         method: 'POST',
         data: {
               id,
               state
         }
      });
   }

   function sendDeleteRequest(id = null) {
      return $.ajax({
         url: '/myAppsForce/mark-as-delete', 
         method: 'POST',
         data: {
               id
         }
      });
   }

   function sendunMarkRequest(id = null) {
      return $.ajax({
         url: '/myAppsForce/mark-as-unread', 
         method: 'POST',
         data: {
               id
         }
      });
   }

   function getNotificationByID(id = null) {
      return $.ajax({
         url: '/myAppsForce/get-notification-byID', 
         method: 'GET',
         data: {
               id
         }
      });
   }
        
    function sendMultipleDeleteRequestq(ids = null) {
      return $.ajax({
         url: '/myAppsForce/mark-multiple-as-delete', 
         method: 'POST',
         data: {
               ids
         }
      });
   }

   function sendunReadMultipleRequests(ids = null) {
      return $.ajax({
         url: '/myAppsForce/mark-multiple-as-unread', 
         method: 'POST',
         data: {
               ids
         }
      });
   }

   function sendMultipleMarkRequest(ids = null) {
      return $.ajax({
         url: '/myAppsForce/mark-multiple-as-read', 
         method: 'POST',
         data: {
               ids
         }
      });
   }
      
  </script>
@endsection
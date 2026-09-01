<body class="app sidebar-mini">
    <!--Loader-->
    <div id="global-loader">
        <img src="{{ asset('images/loader.svg') }}" class="loader-img" alt="">
    </div>
    <!--Loader-->

    <!--Page-->
    <div class="page">
        <div class="page-main h-100">

            <!--App-Header-->
            <div class="app-header1 header py-2 d-flex">
                <div class="container-fluid">
                    <div class="d-flex">
                        <a class="header-brand">
                            <span style="color:#ffffff; font-size: 24px;" class="header-brand-img mobile-logo">ef</span>
                        </a>
                        <a aria-label="Hide Sidebar" class="app-sidebar__toggle" data-bs-toggle="sidebar"
                            href="javascript:void(0)"><i class=""><svg xmlns="http://www.w3.org/2000/svg"
                                    height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                                    <path d="M0 0h24v24H0V0z" fill="none" />
                                    <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z" />
                                </svg></i></a>

                        <div class="d-flex order-lg-2 ms-auto heaader-right">
                            <button class="navbar-toggler navresponsive-toggler d-md-none" type="button"
                                data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-4"
                                aria-controls="navbarSupportedContent-4" aria-expanded="false"
                                aria-label="Toggle navigation">
                                <i class="fe fe-more-vertical header-icons navbar-toggler-icon"></i>
                            </button>
                            <div class="p-0 mb-0 navbar navbar-expand-lg  responsive-navbar navbar-dark  ">
                                <div class="navbar-collapse collapse" id="navbarSupportedContent-4">
                                    <div class="d-flex">                                
                                        <div class="dropdown header-user ">
                                            <a href="javascript:void(0)" class="nav-link leading-none user-img"
                                                data-bs-toggle="dropdown">
                                                <!-- <img src="" alt="profile-img" class="avatar ms-2">  -->
                                                <strong
                                                    class="text-dark"
                                                    style="margin: 0px 10px;">Guest User</strong>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
        <!--/App-Header-->

        <!-- Sidebar menu-->
        <div class=" app-sidebar__overlay" data-bs-toggle="sidebar">
        </div>
            <aside class="app-sidebar doc-sidebar">
                <a class="header-brand sidemenu-header-brand" href="{{ url('/') }}">
                    <img src="{{ asset('your_logo_here.png') }}"
                        class="header-brand-img desktop-logo" alt="">
                    <span style="color:#ffffff; font-size: 24px;" class="header-brand-img mobile-logo">LMS</span>
                </a>

                <ul class="side-menu">

                </ul>
            </aside>
        </div>
    </div>
</body>
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use Auth;
use App\Models\Post;
use App\Models\Section;
use App\Models\Subsection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use App\Models\Countries;
use Illuminate\Support\Facades\Storage;
use DateTime;

//use Illuminate\Foundation\Auth\AuthenticatesUsers;
//use Session;

class HomeController extends Controller {

    private $post;
    private $user;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PostRepository $postRepository, UserRepository $UserRepository) {
        $this->post = $postRepository;
        $this->user = $UserRepository;
    }

    /**
     * Show the application home.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request) {
        $top10Post = $this->post->getLivePost();
        if (Auth::check()) {
//            $transvasal['Actualités'] = $this->post->getSectionTransvasalArticles('Actualités', 'Véhicules & Artillerie', 'Level 1');
            $transvasal["Appels d'offres"] = $this->post->getSectionTransvasalArticles("Appels d'Offres", 'Véhicules & Artillerie', 'Level 3');
            $transvasal["hed2act"] = $this->post->getSectionTransvasalArticles("Actualités", 'Véhicules & Artillerie', 'Level 3');
            $info['category'] = 'Véhicules & Artillerie';
        } else {
//            $transvasal['Actualités'] = $this->post->getTransvasalArticles('Actualités', 'Level 1');
            $transvasal["Appels d'offres"] = $this->post->getTransvasalArticles("Appels d'Offres", 'Level 3');
            $transvasal['hed2act'] = $this->post->getTransvasalArticles('Actualités', 'Level 2');
            $info['category'] = '';
        }
        $info['top10Post'] = $top10Post;
        $info['transvasal'] = $transvasal;
        if (Auth::check()) {
            return view('privatehome', compact('info'));
        } else {
            return view('home', compact('info'));
        }
    }

    public function getSectionData(Request $request) {
        $transvasal["Appels d'offres"] = $this->post->getSectionTransvasalArticles("Appels d'Offres", $request->section, 'Level 3');
        $transvasal["hed2act"] = $this->post->getSectionTransvasalArticles("Actualités", $request->section, 'Level 3');
        $outActualites = $hed2act = $appels = '';
        foreach ($transvasal["Appels d'offres"] as $key => $lpost) {
            $lpost['section'] = json_decode($lpost['section'], true);
            $imageArray = explode(', ', $lpost['article_image']);
            if (substr($lpost['website'], 0, 7) == "http://" || substr($lpost['website'], 0, 8) == "https://") {
                $web = $lpost['website'];
            } else {
                $web = 'https://' . $lpost['website'];
            }
            $appels .= '<div class="col-lg-4 col-md-4 col-sm-12 layout-combo">
                                        <article class="gridlove-post gridlove-post-c gridlove-box  post-175 post type-post status-publish format-audio has-post-thumbnail hentry category-travel tag-blog tag-magazine tag-technology-2 post_format-post-format-audio">
                                            <div class="box-inner-p">
                                                <div class="box-inner-ellipsis">
                                                    <div style="margin: 0px; padding: 0px; border: 0px;">
                                                        <div class="entry-category">
                                                            <div class="head_row">';
            if ($lpost['subsection'] == '') {
                foreach ($lpost['section'] as $svalue) {
                    $appels .= '<a href="' . route('cat-listing', $svalue) . '" class="gridlove-cat ' . getClassifiedIcon($svalue) . '">' . $svalue . '</a>';
                }
            } else {
                $appels .= '<a href="' . route('sub-cat-listing', $lpost['subsection']) . '" class="gridlove-cat ' . getClassifiedIcon(getSectionName($lpost['subsection'])) . '">' . getSubSectionName($lpost['subsection']) . '</a>';
            }
            if ($lpost['country'] != '') {
                $appels .= '<a href="#" class="gridlove-cat" style="background-color: grey;">' . search_country($lpost['country']) . '</a>';
            }
            $appels .= '</div><span class = "gridlove-format-icon">';
            if ($lpost['country'] != '') {
                $appels .= '<img src = "' . asset('/images/flags/' . $lpost['country'] . '.png') . '" class = "attachment-gridlove-b7"/>';
            } else {
                $appels .= '<img src = "' . asset('/images/flags/AAA.png') . '" class = "attachment-gridlove-b7"/>';
            }
            $appels .= '</span></div>
                        <h2 class = "entry-title h3">
                        <a href = "' . route('post-details') . '?article=' . $lpost['guid'] . '">' . $lpost['article_title'] . '</a></h2>
                        </div>
                        </div>
                        <div class = "entry-content">
                        <div class = "entry-content-list">
                        <p>' . strip_tags($lpost['article_content']) . '</p>
                        </div>
                        </div>
                        <div class="entry-meta">
                                                    <div class="meta-item meta-date mr-4">
                                                        <span class="updated">' . dateToFrench($lpost['date_publication'], 'j F Y') . '</span>
                                                    </div>
                                                    <div class="meta-item fa fa-link">
                                                        <a href="' . $web . '" target="_blank">' . $lpost['reference'] . '</a>
                                                    </div>
                                                </div>
                        </div>
                        </article>
                        </div>';
            $last_id = $lpost['id'];
        }
        $appels .= '<nav class="gridlove-pagination gridlove-load-more">
                                        <a data-id="' . $last_id . '" data-url="' . route('load-ao-data') . '" id="load_more_button_ao" style="color: #FFF;">Afficher plus</a>
                                        <div class="gridlove-loader" style="display: none;">
                                            <div class="double-bounce1"></div>
                                            <div class="double-bounce2"></div>
                                        </div>
                                    </nav>';

        foreach ($transvasal['hed2act'] as $key => $lpost) {
            $imageArray = explode(', ', $lpost['article_image']);
            $lpost['section'] = json_decode($lpost['section'], true);
            if (substr($lpost['website'], 0, 7) == "http://" || substr($lpost['website'], 0, 8) == "https://") {
                $web = $lpost['website'];
            } else {
                $web = 'https://' . $lpost['website'];
            }
            $hed2act .= '<div class="col-lg-4 col-md-6 col-sm-12 layout-simple">
                                        <article class="gridlove-post gridlove-post-a gridlove-box  post-151 post type-post status-publish format-standard has-post-thumbnail hentry category-travel">
                                            <div class="entry-image">';
            if ($lpost['article_image'] != '') {
                $hed2act .= '<a href="' . route('post-details') . '?article=' . $lpost['guid'] . '" class="' . ($lpost['level'] == 'Level 3' ? "" : "type1_box-a") . '" title="' . $lpost['article_title'] . '">
                                                <img width="370" height="150" src="' . postgetImageUrl($imageArray[0], $lpost['created_at']) . '" class="attachment-gridlove-a4 size-gridlove-a4 wp-post-image" alt="" loading="lazy">
                                            </a>';
            } else {
                $hed2act .= '<a href="' . route('post-details') . '?article=' . $lpost['guid'] . '" class="' . ($lpost['level'] == 'Level 3' ? "" : "type1_box-a") . '" title="' . $lpost['article_title'] . '">
                                                <img width="370" height="150" src="' . $lpost['url_image'] . '" class="attachment-gridlove-a4 size-gridlove-a4 wp-post-image" alt="" loading="lazy">
                                            </a>';
            }
            $hed2act .= '<div class="entry-category">';
            if ($lpost['subsection'] == '') {
                foreach ($lpost['section'] as $svalue) {
                    $hed2act .= '<a href="' . route('cat-listing', $svalue) . '" class="gridlove-cat ' . getClassifiedIcon($svalue) . '">' . $svalue . '</a>';
                }
            } else {
                $hed2act .= '<a href="' . route('sub-cat-listing', $lpost['subsection']) . '" class="gridlove-cat ' . getClassifiedIcon(getSectionName($lpost['subsection'])) . '">' . getSubSectionName($lpost['subsection']) . '</a>';
            }
            $hed2act .= '</div></div><div>
                                        <div class="box-inner-p">
                                            <div class="box-inner-ellipsis">
                                                <div style="margin: 0px; padding: 0px; border: 0px;">
                                                    <h2 class="entry-title h3">
                                                        <a href="' . route('post-details') . '?article=' . $lpost['guid'] . '">' . $lpost['article_title'] . '</a></h2>
                                                    </h2>
                                                </div>
                                            </div>
                                            <div class="entry-content">
                                                <div class="entry-content-list">
                                                    <p>' . strip_tags($lpost['article_content']) . '</p>
                                                </div>
                                            </div>
                                            <div class="entry-meta">
                                                    <div class="meta-item meta-date mr-4">
                                                        <span class="updated">' . dateToFrench($lpost['date_publication'], 'j F Y') . '</span>
                                                    </div>
                                                    <div class="meta-item fa fa-link">
                                                        <a href="' . $web . '" target="_blank">' . $lpost['reference'] . '</a>
                                                    </div>
                                                </div>
                                        </div>
                                    </article>
                                </div>';
            $last_id = $lpost['id'];
        }
        $hed2act .= '<nav class="gridlove-pagination gridlove-load-more">
                                        <a data-id="' . $last_id . '" data-url="' . route('load-act-data') . '" id="load_more_button_act" style="color: #FFF;">Afficher plus</a>
                                        <div class="gridlove-loader" style="display: none;">
                                            <div class="double-bounce1"></div>
                                            <div class="double-bounce2"></div>
                                        </div>
                                    </nav>';
        $dataStore = array();
        $dataStore['appels'] = $appels;
        $dataStore['hed2act'] = $hed2act;
        return json_encode($dataStore);
    }

    /**
     * Show the post details view.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function details(Request $request) {
        $postDetails = $this->post->postDetails($request->article);
        $transvasal['Actualités'] = $this->post->getTransvasalArticles('Actualités', 'Level 1');
        $transvasal["Appels d'offres"] = $this->post->getTransvasalArticles("Appels d'Offres", 'Level 3');
        $transvasal['hed2act'] = $this->post->getTransvasalArticles('Actualités', 'Level 2');
        $info = $postDetails;
        $info['transvasal'] = $transvasal;
        $info['count'] = $this->post->getTransvasalArticlesCount("Appels d'Offres");
        $info['recentArticles'] = $this->post->getRecentTransvasalArticles("Appels d'Offres");
        $section = json_decode($postDetails->section, true);
        // get previous user id
        $info['previous'] = $this->post->getPreviousArticle($section, $postDetails);
        // get next user id
        $info['next'] = $this->post->getNextArticle($section, $postDetails);
//        dd($info);
        if ($postDetails->transversal == "Appels d'Offres") {
            return view('article.type2', compact('info'));
        } else {
            return view('article.type3', compact('info'));
        }
    }

    public function searchResultPage(Request $request) {
        $info['section'] = Section::all();
        $info['countries'] = Countries::all();
        $info['search'] = $request;
        return view('search_result', compact('info'));
    }

    public function searchResult(Request $request) {
        $info['section'] = Section::all();
        $transvasal = $this->post->getSearchArticles($request);
        $info['search'] = $request;
        if (null !== $request->section) {
            $section = Section::whereIn('name', $request->section)->get();
            $idArray = array();
            foreach ($section as $svalue) {
                $idArray[] = $svalue->id;
            }
            $info['subsectionList'] = Subsection::whereIn('section', $idArray)->get();
        } else {
            $info['subsectionList'] = array();
        }
        $info['countries'] = Countries::all();
        $info['transvasal'] = $transvasal;

        return view('search_result', compact('info'));
    }

    public function categoryListing(Request $request) {
        $transvasal['Actualités'] = $this->post->getSectionTransvasalArticles('Actualités', $request->cat, 'Level 1');
        $transvasal["Appels d'offres"] = $this->post->getSectionTransvasalArticles("Appels d'Offres", $request->cat, 'Level 3');
        $transvasal["hed2act"] = $this->post->getSectionTransvasalArticles("Actualités", $request->cat, 'Level 2');
        $top10Post = $this->post->getLivePost();
        $info['category'] = $request->cat;
        $info['transvasal'] = $transvasal;
        $info['top10Post'] = $top10Post;
        return view('cat', compact('info'));
    }

    public function typeListing(Request $request) {
        $request->request->add(['section' => array()]);
        $request->request->add(['subsection' => array()]);
        $request->request->add(['search' => '']);
        $request->request->add(['typedavis' => $request->type]);
        $request->request->add(['country' => array()]);
        $transvasal = $this->post->getSearchArticles($request);
        $info['section'] = Section::all();
        $info['subsectionList'] = array();
        $info['search'] = $request;
        $info['transvasal'] = $transvasal;
        $info['countries'] = Countries::all();
        return view('search_result', compact('info'));
    }

    public function subcategoryListing(Request $request, $subsection) {
        $subsectionList = Subsection::where('id', $subsection)->get();
        $section = Section::select(['id', 'name'])->find($subsectionList[0]->section);
        $request->request->add(['section' => array($section->name)]);
        $request->request->add(['subsection' => array($subsection)]);
        $request->request->add(['search' => '']);
        $request->request->add(['typedavis' => "Appels d'Offres"]);
        $request->request->add(['country' => array()]);
        $info['section'] = Section::all();
        $transvasal = $this->post->getSearchArticles($request);
        $info['subsectionList'] = Subsection::where('section', $section->id)->get();
        $info['search'] = $request;
        $info['transvasal'] = $transvasal;
        $info['countries'] = Countries::all();
        return view('search_result', compact('info'));
    }

    public function countryListing(Request $request, $country) {
        $request->request->add(['section' => array()]);
        $request->request->add(['subsection' => array()]);
        $request->request->add(['search' => '']);
        $request->request->add(['typedavis' => "Appels d'Offres"]);
        $request->request->add(['country' => array($country)]);
        $transvasal = $this->post->getSearchArticles($request);
        $info['section'] = Section::all();
        $info['search'] = $request;
        $info['transvasal'] = $transvasal;
        $info['countries'] = Countries::all();
        return view('search_result', compact('info'));
    }

    /**
     * Show the application home page loadmore.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function loadMoreAOData(Request $request) {
        if ($request->id > 0) {
            if ($request->home == 1) {
                $postList = $this->post->loadMoreSectionArticles("Appels d'Offres", $request->section, 'Level 3', $request->id);
            } else {
                $postList = $this->post->getLoadMoreSearchResult($request->id, $request);
            }
        }
        $appels = '';
        $last_id = '';
        if (isset($postList) && null !== $postList && count($postList) > 0) {
            foreach ($postList as $lpost) {
                $lpost['section'] = json_decode($lpost['section'], true);
                $imageArray = explode(', ', $lpost['article_image']);
                if (substr($lpost['website'], 0, 7) == "http://" || substr($lpost['website'], 0, 8) == "https://") {
                    $web = $lpost['website'];
                } else {
                    $web = 'https://' . $lpost['website'];
                }
                $appels .= '<div class="col-lg-4 col-md-4 col-sm-12 layout-combo">
                                        <article class="gridlove-post gridlove-post-c gridlove-box  post-175 post type-post status-publish format-audio has-post-thumbnail hentry category-travel tag-blog tag-magazine tag-technology-2 post_format-post-format-audio">
                                            <div class="box-inner-p">
                                                <div class="box-inner-ellipsis">
                                                    <div style="margin: 0px; padding: 0px; border: 0px;">
                                                        <div class="entry-category">
                                                            <div class="head_row">';
                if ($lpost['subsection'] == '') {
                    foreach ($lpost['section'] as $svalue) {
                        $appels .= '<a href="' . route('cat-listing', $svalue) . '" class="gridlove-cat ' . getClassifiedIcon($svalue) . '">' . $svalue . '</a>';
                    }
                } else {
                    $appels .= '<a href="' . route('sub-cat-listing', $lpost['subsection']) . '" class="gridlove-cat ' . getClassifiedIcon(getSectionName($lpost['subsection'])) . '">' . getSubSectionName($lpost['subsection']) . '</a>';
                }
                if ($lpost['country'] != '') {
                    $appels .= '<a href="#" class="gridlove-cat" style="background-color: grey;">' . search_country($lpost['country']) . '</a>';
                }
                $appels .= '</div><span class = "gridlove-format-icon">';
                if ($lpost['country'] != '') {
                    $appels .= '<img src = "' . asset('/images/flags/' . $lpost['country'] . '.png') . '" class = "attachment-gridlove-b7"/>';
                } else {
                    $appels .= '<img src = "' . asset('/images/flags/AAA.png') . '" class = "attachment-gridlove-b7"/>';
                }
                $appels .= '</span></div>
                        <h2 class = "entry-title h3">
                        <a href = "' . route('post-details') . '?article=' . $lpost['guid'] . '">' . $lpost['article_title'] . '</a></h2>
                        </div>
                        </div>
                        <div class = "entry-content">
                        <div class = "entry-content-list">
                        <p>' . strip_tags($lpost['article_content']) . '</p>
                        </div>
                        </div>
                        <div class="entry-meta">
                                                    <div class="meta-item meta-date mr-4">
                                                        <span class="updated">' . dateToFrench($lpost['date_publication'], 'j F Y') . '</span>
                                                    </div>
                                                    <div class="meta-item fa fa-link">
                                                        <a href="' . $web . '" target="_blank">' . $lpost['reference'] . '</a>
                                                    </div>
                                                </div>
                        </div>
                        </article>
                        </div>';
                $last_id = $lpost['id'];
            }
            $appels .= '<nav class="gridlove-pagination gridlove-load-more">
                                        <a data-id="' . $last_id . '" data-url="' . route('load-ao-data') . '" id="load_more_button_ao" style="color: #FFF;">Afficher plus d’AO</a>
                                        <div class="gridlove-loader" style="display: none;">
                                            <div class="double-bounce1"></div>
                                            <div class="double-bounce2"></div>
                                        </div>
                                    </nav>';
        } else {
            $appels .= '';
        }
        return $appels;
    }

    public function loadMoreACTData(Request $request) {
        if ($request->id > 0) {
            if ($request->home == 1) {
                $postList = $this->post->loadMoreSectionArticles("Actualités", $request->section, 'Level 3', $request->id);
            } else {
                $postList = $this->post->getLoadMoreSearchResult($request->id, $request);
            }
        }
        $hed2act = '';
        $last_id = '';
        if (isset($postList) && null !== $postList && count($postList) > 0) {
            foreach ($postList as $lpost) {
                $imageArray = explode(', ', $lpost['article_image']);
                $lpost['section'] = json_decode($lpost['section'], true);
                if (substr($lpost['website'], 0, 7) == "http://" || substr($lpost['website'], 0, 8) == "https://") {
                    $web = $lpost['website'];
                } else {
                    $web = 'https://' . $lpost['website'];
                }
                $hed2act .= '<div class="col-lg-4 col-md-6 col-sm-12 layout-simple">
                                        <article class="gridlove-post gridlove-post-a gridlove-box  post-151 post type-post status-publish format-standard has-post-thumbnail hentry category-travel">
                                            <div class="entry-image">';
                if ($lpost['article_image'] != '') {
                    $hed2act .= '<a href="' . route('post-details') . '?article=' . $lpost['guid'] . '" class="' . ($lpost['level'] == 'Level 3' ? "" : "type1_box-a") . '" title="' . $lpost['article_title'] . '">
                                                <img width="370" height="150" src="' . postgetImageUrl($imageArray[0], $lpost['created_at']) . '" class="attachment-gridlove-a4 size-gridlove-a4 wp-post-image" alt="" loading="lazy">
                                            </a>';
                } else {
                    $hed2act .= '<a href="' . route('post-details') . '?article=' . $lpost['guid'] . '" title="' . $lpost['article_title'] . '">
                                                <img width="370" height="150" src="' . $lpost['url_image'] . '" class="attachment-gridlove-a4 size-gridlove-a4 wp-post-image" alt="" loading="lazy">
                                            </a>';
                }
                $hed2act .= '<div class="entry-category">';
                if ($lpost['subsection'] == '') {
                    foreach ($lpost['section'] as $svalue) {
                        $hed2act .= '<a href="' . route('cat-listing', $svalue) . '" class="gridlove-cat ' . getClassifiedIcon($svalue) . '">' . $svalue . '</a>';
                    }
                } else {
                    $hed2act .= '<a href="' . route('sub-cat-listing', $lpost['subsection']) . '" class="gridlove-cat ' . getClassifiedIcon(getSectionName($lpost['subsection'])) . '">' . getSubSectionName($lpost['subsection']) . '</a>';
                }
                $hed2act .= '</div></div><div>
                                        <div class="box-inner-p">
                                            <div class="box-inner-ellipsis">
                                                <div style="margin: 0px; padding: 0px; border: 0px;">
                                                    <h2 class="entry-title h3">
                                                        <a href="' . route('post-details') . '?article=' . $lpost['guid'] . '">' . $lpost['article_title'] . '</a></h2>
                                                    </h2>
                                                </div>
                                            </div>
                                            <div class="entry-content">
                                                <div class="entry-content-list">
                                                    <p>' . strip_tags($lpost['article_content']) . '</p>
                                                </div>
                                            </div>
                                            <div class="entry-meta">
                                                    <div class="meta-item meta-date mr-4">
                                                        <span class="updated">' . dateToFrench($lpost['date_publication'], 'j F Y') . '</span>
                                                    </div>
                                                    <div class="meta-item fa fa-link">
                                                        <a href="' . $web . '" target="_blank">' . $lpost['reference'] . '</a>
                                                    </div>
                                                </div>
                                        </div>
                                    </article>
                                </div>';
                $last_id = $lpost['id'];
            }
            $hed2act .= '<nav class="gridlove-pagination gridlove-load-more">
                                        <a data-id="' . $last_id . '" data-url="' . route('load-act-data') . '" id="load_more_button_act" style="color: #FFF;">Afficher plus d’actus</a>
                                        <div class="gridlove-loader" style="display: none;">
                                            <div class="double-bounce1"></div>
                                            <div class="double-bounce2"></div>
                                        </div>
                                    </nav>';
        } else {
            $hed2act .= '';
        }
        return $hed2act;
    }

    public function addarticles() {
        $cname = $dataStore = array();
        set_time_limit(0);
        ini_set('memory_limit', '512M');
//        $country = getCountryList();
//        foreach ($country as $cvalue) {
//            $cname[] = $cvalue['name'];
//        }
        $post = Post::select(['id', 'reference', 'article_content'])->where('transversal', "Appels d'Offres")->get();
        foreach ($post as $key => $value) {
//            $arRef = explode('.', $value['reference']);
//            $myLastElement = $arRef[array_key_last($arRef)];
//            if ($myLastElement != 'org' || $myLastElement != 'com') {
//                $countryCodeArray = countrySearch(array('name' => $myLastElement));
//                if (isset($countryCodeArray['Country_Alpha3code']) && null !== $countryCodeArray['Country_Alpha3code']) {
//                    $code = strtoupper($countryCodeArray['Country_Alpha3code']);
//                    Post::where('id', $value['id'])->update(['country' => $code]);
//                } else {
//                    echo $myLastElement . '<br>';
//                }
//            }
//            if (strpos($value['reference'], 'acquisition.army.mil') !== false) {
//                Post::where('id', $value['id'])->update(['country' => 'USA']);
//            } 
//            if (strpos($value['article_content'], '+44') !== false) {
//                Post::where('id', $value['id'])->update(['country' => 'USA']);
//            }
//            foreach ($cname as $cnvalue) {
//              if (strpos($value['article_content'], strtoupper($cnvalue)) !== false) {
//                    $dataStore[$value['id']]['name'] = $cnvalue;
//                    $countryCodeArray = countrySearch(array('name' => $cnvalue));
//                    $code = $dataStore[$value['id']]['code'] = strtoupper($countryCodeArray['Country_Alpha3code']);
//                    Post::where('id', $value['id'])->update(['country' => $code]);
//                }
//            }
        }
        dd($post);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function autocomplete(Request $request) {

        $xmlString = file_get_contents(storage_path() . "/json/Homeland_Security_2012-2022.xml");
        $xmlObject = simplexml_load_string($xmlString);

        $json = json_encode($xmlObject);
        $array = json_decode($json, true);
        $path1 = public_path('images/' . date('Y/m/d'));
        if (!file_exists($path1)) {
            mkdir($path1, 0777, true);
        }
        foreach ($array['node'] as $key => $value) {
            if (isset($value['properties']['custom_propTitre']) && !empty($value['properties']['custom_propTitre'])) {
                $uuid = explode('SpacesStore/', $value['nodeRef'])[1];
                $url = 'http://www.gicat.info/images/' . $uuid . '.png';
                if (@getimagesize($url)) {
                    $url = 'http://www.gicat.info/images/' . $uuid . '.png';
                } else {
                    $url = 'http://www.gicat.info/alfresco/service/gicat-info/workspace/SpacesStore/' . $uuid . '/largeImage';
                }
                if (@getimagesize($url)) {
                    $contents = file_get_contents($url);
                    $name = $uuid . '.png';
                    file_put_contents($path1 . '/' . $name, $contents);
                } else {
                    $name = '';
                }
                if (strpos($value['path'], 'Panier_HS') !== false || strpos($value['path'], 'Archives Homeland Security') !== false) {
                    $section = '["Homeland Security"]';
                } else if (strpos($value['path'], 'Panier_Vehicule_Equipement') !== false) {
                    $section = '["Véhicules & Artillerie"]';
                } else if (strpos($value['path'], 'Panier_C4ISR') !== false) {
                    $section = '["C4ISR"]';
                } else if (strpos($value['path'], 'Panier_Simulation') !== false || strpos($value['path'], 'Panier_Simu') !== false) {
                    $section = '["Simulation"]';
                } else if (strpos($value['path'], 'Panier_AT') !== false) {
                    $section = '["Aéro-Terrestre"]';
                } else if (strpos($value['path'], 'Panier_SL') !== false) {
                    $section = '["Soutien logistique"]';
                }
                if (strpos($value['path'], '/Top/') !== false) {
                    $level = 'Level 1';
                } else if (strpos($value['path'], '/Top2/') !== false || strpos($value['path'], '/Top3/') !== false) {
                    $level = 'Level 2';
                } else {
                    $level = 'Level 3';
                }
                if (strpos($value['path'], '/AO/') !== false) {
                    $transversal = "Appels d'Offres";
                } else {
                    $transversal = 'Actualités';
                }
                if (!empty($value['properties']['custom_propBody'])) {
                    if (strpos($value['properties']['custom_propBody'], 'Country:') !== false) {
                        $countryArray = explode('Country:', $value['properties']['custom_propBody']);
                        $countryName = ltrim(explode("\n", $countryArray[1])[0]);
                        $countryCodeArray = countrySearch(array('name' => $countryName));
                        if (isset($countryCodeArray['Country_Alpha3code']) && null !== $countryCodeArray['Country_Alpha3code']) {
                            $countryCode = strtoupper($countryCodeArray['Country_Alpha3code']);
                        } else {
                            $countryCode = '';
                        }
                    } else {
                        $countryCode = '';
                    }
                } else {
                    $countryCode = '';
                }

                $dataStore['article_author'] = 1;
                $dataStore['article_title'] = $value['properties']['custom_propTitre'];
                $dataStore['article_collecteur'] = $value['properties']['custom_propCollecteur'];
                $dataStore['reference'] = (!empty($value['properties']['custom_propSource']) ? $value['properties']['custom_propSource'] : "");
                $dataStore['date_publication'] = date('Y-m-d', strtotime($value['properties']['custom_propDate']));
                $dataStore['article_content'] = (!empty($value['properties']['custom_propBody']) ? $value['properties']['custom_propBody'] : "");
                $dataStore['article_image'] = $name;
                $dataStore['website'] = (isset($value['properties']['custom_propUrl']) ? $value['properties']['custom_propUrl'] : "");
                $dataStore['article_status'] = 'approved';
                $dataStore['commentaire'] = $value['properties']['cm_description'];
                $dataStore['date_limite'] = null;
                $dataStore['guid'] = $value['nodeRef'];
                $dataStore['come_place'] = 'script';
                $dataStore['section'] = $section;
                $dataStore['subsection'] = '';
                $dataStore['level'] = $level;
                $dataStore['transversal'] = $transversal;
                $dataStore['country'] = $countryCode;
                $dataStore['created_at'] = date('Y-m-d H:i:s');
                $dataStore['xml_created'] = date('Y-m-d H:i:s', strtotime($value['properties']['cm_created']));
                $dataStore['updated_at'] = date('Y-m-d H:i:s', strtotime($value['properties']['cm_modified']));
                //                dd($dataStore);
                DB::table('articles')->insert([$dataStore]);
            }
        }
    }

}

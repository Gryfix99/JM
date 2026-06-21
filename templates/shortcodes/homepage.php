<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="jm-main-wrapper pjm-fade-in">
    
    <header class="jm-hero-section">
        <div class="jm-hero-overlay"></div>
        <div class="jm-hero-content jm-animate-up">
            <span class="jm-hero-badge">Profesjonalne Tłumaczenia</span>
            <h1 class="jm-hero-title">CERTYFIKOWANY TŁUMACZ<br>POLSKIEGO JĘZYKA MIGOWEGO</h1>
            <p class="jm-hero-subtitle">Twój partner w dostępności. Obsługa firm, urzędów i kultury w Warszawie i całej Polsce. Profesjonalizm potwierdzony certyfikatem T2.</p>
            <div class="jm-hero-actions">
                <a href="#kontakt" class="jm-btn-primary jm-scroll-link">Darmowa wycena</a>
                </div>
        </div>
    </header>

    <div class="jm-container jm-quick-nav-section">
        <div class="jm-quick-nav-grid">
            <a href="/abonamenty" class="jm-nav-card jm-animate-up">
                <span class="material-symbols-outlined">diamond</span>
                <h4>Abonamenty<br>Dostępności</h4>
            </a>
            <a href="/na-zywo" class="jm-nav-card jm-animate-up" style="transition-delay: 0.1s">
                <span class="material-symbols-outlined">interpreter_mode</span>
                <h4>Tłumaczenia<br>Na Żywo</h4>
            </a>
            <a href="/nagrania" class="jm-nav-card jm-animate-up" style="transition-delay: 0.2s">
                <span class="material-symbols-outlined">video_file</span>
                <h4>Tłumaczenie<br>nagrań</h4>
            </a>
            <a href="/teksty" class="jm-nav-card jm-animate-up" style="transition-delay: 0.2s">
                <span class="material-symbols-outlined">docs</span>
                <h4>Tłumaczenie<br>tekstów</h4>
            </a>
            <a href="/petla-indukcyjna" class="jm-nav-card jm-animate-up" style="transition-delay: 0.3s">
                <span class="material-symbols-outlined">hearing</span>
                <h4>Pętle<br>Indukcyjne</h4>
            </a>
            <div class="jm-nav-card disabled jm-animate-up" style="transition-delay: 0.4s">
                <span class="material-symbols-outlined">school</span>
                <h4>Szkolenia<br><span class="jm-badge-soon">WKRÓTCE</span></h4>
            </div>
        </div>
    </div>
    
    <?php
    if ( ! $is_logged_in ) {
        $cta_path = defined('PJM_CALC_PATH') ? PJM_CALC_PATH . 'templates/components/cta-login.php' : '';
        if ( file_exists( $cta_path ) ) include $cta_path;
    }
    ?>

    <section class="jm-section bg-light">
        <div class="jm-container">
            <div class="jm-center-head jm-animate-up">
                <h2 class="jm-section-head">Kompleksowe wsparcie</h2>
                <p class="jm-lead-text">Zapewniam pełen wachlarz usług dostępnościowych. Od tłumaczenia tekstów po produkcję wideo.</p>
            </div>

            <div class="jm-services-row-3">
                <div class="jm-service-box jm-animate-up">
                    <div class="jm-service-icon-wrap"><span class="material-symbols-outlined">interpreter_mode</span></div>
                    <div class="jm-service-title">Tłumaczenie PJM na żywo</div>
                    <div class="jm-service-desc">Profesjonalne tłumaczenia na żywo: konferencje, szkolenia, webinary. Obsługa stacjonarna oraz online.</div>
                </div>
                <div class="jm-service-box jm-animate-up" style="transition-delay: 0.1s">
                    <div class="jm-service-icon-wrap"><span class="material-symbols-outlined">verified</span></div>
                    <div class="jm-service-title">Certyfikat T2</div>
                    <div class="jm-service-desc">Najwyższy certyfikat tłumacza PJM wydawany przez PZG. Gwarancja jakości i etyki zawodowej.</div>
                </div>
                <div class="jm-service-box jm-animate-up" style="transition-delay: 0.2s">
                    <div class="jm-service-icon-wrap"><span class="material-symbols-outlined">videocam</span></div>
                    <div class="jm-service-title">Nagrania i teksty w PJM</div>
                    <div class="jm-service-desc">Kompleksowe tłumaczenie tekstów pisanych na PJM oraz produkcja filmów w studio z greenscreenem.</div>
                </div>
            </div>

            <div class="jm-services-row-2">
                <div class="jm-service-box jm-animate-up">
                    <div class="jm-service-icon-wrap"><span class="material-symbols-outlined">closed_caption</span></div>
                    <div class="jm-service-title">Napisy i dostępność</div>
                    <div class="jm-service-desc">Tworzenie dostępnych napisów oraz transkrypcji tekstów.</div>
                </div>
                <div class="jm-service-box jm-animate-up" style="transition-delay: 0.1s">
                    <div class="jm-service-icon-wrap"><span class="material-symbols-outlined">hearing</span></div>
                    <div class="jm-service-title">Pętle indukcyjne</div>
                    <div class="jm-service-desc">Wynajem i instalacja certyfikowanych pętli indukcyjnych dla osób słabosłyszących.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="jm-section" id="o-mnie">
        <div class="jm-container">
            <div class="jm-about-split">
                <div class="jm-about-content jm-animate-up">
                    <h2 class="jm-section-head">Cześć, tu <span style="color: var(--pjm-orange);">Janusz</span></h2>
                    <h4 style="font-size: 20px; font-weight: 600; margin-bottom: 25px; color: #555;">Twój tłumacz PJM</h4>
                    
                    <p>Jestem Janusz. Od lat aktywnie działam w świecie języka migowego oraz Kultury Głuchych. Na co dzień żyję w tym środowisku, co pozwala mi łączyć <strong>perfekcyjną znajomość PJM</strong> z wyczuciem kontekstu kulturowego.</p>
                    <p><strong>Jako profesjonalny tłumacz języka migowego pracuję od ponad 5 lat.</strong> Moje doświadczenie obejmuje współpracę z Polskim Związkiem Głuchych oraz największymi instytucjami w kraju.</p>
                    <p>Aktualnie wspieram <strong>firmy, urzędy, instytucje kultury i fundacje</strong>, pomagając im wdrażać realną dostępność.</p>
                    
                    <a href="#kontakt" class="jm-btn-primary jm-scroll-link" style="margin-top: 20px;">Porozmawiajmy o współpracy</a>
                </div>
                
                <div class="jm-about-img-wrap jm-animate-up">
                    <div class="jm-about-bg-decor"></div>
                    <img loading="lazy" src="https://januszmigowego.pl/wp-content/uploads/2023/10/1685605333217-e1698390210992-768x584.jpeg" alt="Janusz Pierzchalski" class="jm-about-img">
                </div>
            </div>
        </div>
    </section>

    <div class="jm-stats-bar">
        <div class="jm-container">
            <h3 style="font-size: 24px; font-weight: 300; margin-bottom: 40px; color: #e0e0e0;">Liczby mówią same za siebie</h3>
            <div class="jm-stats-grid">
                <div class="jm-stat-item jm-animate-up">
                    <span class="jm-stat-num">5+</span>
                    <span class="jm-stat-label">Lat doświadczenia</span>
                </div>
                <div class="jm-stat-item jm-animate-up" style="transition-delay: 0.1s">
                    <span class="jm-stat-num">100+</span>
                    <span class="jm-stat-label">Zadowolonych Klientów</span>
                </div>
                <div class="jm-stat-item jm-animate-up" style="transition-delay: 0.2s">
                    <span class="jm-stat-num">1000h+</span>
                    <span class="jm-stat-label">Zrealizowanych Tłumaczeń</span>
                </div>
            </div>
        </div>
    </div>

    <section class="jm-section bg-light" id="portfolio">
        <div class="jm-container">
            <div class="jm-center-head jm-animate-up">
                <h2 class="jm-section-head">Zaufali mi</h2>
                <p class="jm-lead-text">Wspieram teatry, instytucje publiczne i biznes w tworzeniu dostępnych wydarzeń.</p>
            </div>
            
            <div class="jm-partners-grid jm-animate-up">
                <a href="https://www.majdanek.eu" target="_blank" class="jm-partner" title="Państwowe Muzeum na Majdanku">
                    <img loading="lazy" src="https://www.majdanek.eu/media/photos/images/b/e/z/6/2/orig_bez62d1049.jpg" alt="Muzeum na Majdanku" style="object-fit: contain;">
                </a>

                <a href="https://www.kopernik.org.pl" target="_blank" class="jm-partner" title="Centrum Nauki Kopernik">
                    <img loading="lazy" src="https://logowik.com/content/uploads/images/centrum-nauki-kopernik2427.logowik.com.webp" alt="Centrum Nauki Kopernik" style="object-fit: contain;">
                </a>

                <a href="https://www.decathlon.pl" target="_blank" class="jm-partner" title="Decathlon">
                    <img loading="lazy" src="https://1000logos.net/wp-content/uploads/2020/03/Decathlon-Logo.png" alt="Decathlon" style="object-fit: contain;">
                </a>

                <a href="https://www.pfron.org.pl" target="_blank" class="jm-partner" title="PFRON">
                    <img loading="lazy" src="https://www.pfron.org.pl/fileadmin/Redakcja/logo/PFRON_wersja_podstawowa_RGB-01.jpg" alt="PFRON" style="object-fit: contain;">
                </a>

                <a href="https://kulturabezbarier.org" target="_blank" class="jm-partner"><img loading="lazy" src="https://januszmigowego.pl/wp-content/uploads/2023/07/log_FKBB.jpg" alt="FKBB"></a>
                <a href="https://polskabezbarier.org" target="_blank" class="jm-partner"><img loading="lazy" src="https://januszmigowego.pl/wp-content/uploads/2023/10/pbb.png" alt="Polska Bez Barier"></a>
                <a href="https://centrumarchitektury.org" target="_blank" class="jm-partner"><img loading="lazy" src="https://januszmigowego.pl/wp-content/uploads/2023/10/CA.png" alt="Centrum Architektury"></a>
                <a href="http://myevergreen.art.pl" target="_blank" class="jm-partner"><img loading="lazy" src="https://januszmigowego.pl/wp-content/uploads/2023/10/myevergreen.png" alt="MyEvergreen"></a>
                <a href="https://lazienki-krolewskie.pl" target="_blank" class="jm-partner"><img loading="lazy" src="https://januszmigowego.pl/wp-content/uploads/2023/07/logo_LK.jpg" alt="Łazienki Królewskie"></a>
                <a href="https://ecs.gda.pl" target="_blank" class="jm-partner"><img loading="lazy" src="https://januszmigowego.pl/wp-content/uploads/2023/07/logo_ECS.jpg" alt="ECS"></a>
                <a href="https://pcd.poznan.pl" target="_blank" class="jm-partner"><img loading="lazy" src="https://januszmigowego.pl/wp-content/uploads/2023/10/PCD.png" alt="PCD"></a>
                <a href="http://michalowice.pl" target="_blank" class="jm-partner"><img loading="lazy" src="https://januszmigowego.pl/wp-content/uploads/2023/10/michalowice.png" alt="Michałowice"></a>
                <a href="https://koj24.pl" target="_blank" class="jm-partner"><img loading="lazy" src="https://januszmigowego.pl/wp-content/uploads/2023/10/koj.png" alt="Koj24"></a>
            </div>
        </div>
    </section>

    <div class="jm-cert">
        <div style="flex: 1;">
            <h2 class="jm-section-head" style="border-color: var(--pjm-dark);">Gwarancja Jakości – Certyfikat T2</h2>
            <p class="jm-text">Posiadam certyfikat tłumacza Polskiego Języka Migowego T2, wydany przez Zarząd Główny PZG. Znajduję się na oficjalnej liście tłumaczy, co gwarantuje najwyższe standardy etyczne i językowe.</p>
            <a href="https://cemn.pzg.org.pl/tlumacze/baza-tlumaczy/" target="_blank" class="btn btn-outline">Sprawdź bazę tłumaczy PZG</a>
        </div>
        <div>
            <img decoding="async" src="https://januszmigowego.pl/wp-content/uploads/2023/12/Certyfikat-210x300.jpg" alt="Certyfikat Tłumacza PJM" class="jm-cert-img">
        </div>
    </div>

    <div id="kontakt" class="jm-contact-grid">
        <div id="kontakt" class="jm-contact-form-col">
            <?php echo do_shortcode('[contact-form-7 id="663" title="Formularz kontaktowy 1" html_class="jm-modern-form jm-contact-simple"]'); ?>
        </div>
        
        <div class="jm-contact-info-col">
            <div class="jm-contact-widget">
                <h3 class="jm-widget-title">Bądźmy w kontakcie</h3>
                <p class="jm-widget-desc">Napisz lub zadzwoń — odpowiemy najszybciej, jak to możliwe.</p>

                <div class="jm-contact-row">
                    <div class="jm-icon-circle"><span class="material-symbols-outlined">call</span></div>
                    <a href="tel:48722150487" class="jm-contact-link">722 150 487</a>
                </div>

                <div class="jm-contact-row">
                    <div class="jm-icon-circle"><span class="material-symbols-outlined">mail</span></div>
                    <a href="mailto:janusz@januszmigowego.pl" class="jm-contact-link">janusz@januszmigowego.pl</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo PJM_CALC_URL . 'assets/js/homepage.js'; ?>"></script>
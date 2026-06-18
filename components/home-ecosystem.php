<section class="home-ecosystem animate-on-scroll">
    <div class="ecosystem-container">
        <header class="ecosystem-header" data-scroll="reveal">
            <h2><?php i18n_e('home.ecosystem_title', [], 'The Interactive Ecosystem'); ?></h2>
            <p><?php i18n_e('home.ecosystem_desc', [], 'Discover how our curated components work in perfect harmony to deliver an unmatched computing experience.'); ?></p>
        </header>

        <div class="ecosystem-explorer" data-default-component="cpu">
            <div class="eco-orbit" aria-hidden="true">
                <span></span><span></span><span></span>
            </div>
            <div class="eco-pulse-grid" aria-hidden="true"></div>
            <div class="eco-connection-copy" data-scroll="reveal" data-scroll-delay="90">
                <span class="eco-copy-kicker"><?php i18n_e('home.eco_copy_kicker', [], 'Interactive Compatibility Map'); ?></span>
                <p><?php i18n_e('home.eco_copy_desc', [], 'Start with the processor, then trace each supporting component to understand how the full build ecosystem locks together.'); ?></p>
            </div>
            <div class="eco-hub">
                <button type="button" class="eco-node eco-node-hub"
                        data-component="cpu"
                        data-connects="motherboard,ram,cooler,gpu,psu"
                        data-label="<?php i18n_e('home.eco_cpu_label', [], 'Processor (CPU)'); ?>"
                        data-desc="<?php i18n_e('home.eco_cpu_desc', [], 'The brain of your PC. We pair high-core-count CPUs with robust cooling to prevent thermal throttling.'); ?>"
                        data-cta-url="products.php?category=processors"
                        data-cta-text="<?php i18n_e('nav.processors'); ?>">
                    <span class="eco-node-glow" aria-hidden="true"></span>
                    <i class="fas fa-microchip"></i>
                    <span class="eco-node-label"><?php i18n_e('nav.processors', [], 'CPU'); ?></span>
                    <span class="eco-node-meta">AM5 / LGA</span>
                </button>
            </div>

            <div class="eco-spokes">
                <button type="button" class="eco-node"
                        data-component="motherboard"
                        data-connects="cpu,ram,gpu,storage,psu"
                        data-label="<?php i18n_e('home.eco_mobo_label', [], 'Motherboard'); ?>"
                        data-desc="<?php i18n_e('home.eco_mobo_desc', [], 'The nervous system. High-end VRMs ensure stable power delivery for overclocking and longevity.'); ?>"
                        data-cta-url="products.php?category=motherboards"
                        data-cta-text="<?php i18n_e('nav.motherboards'); ?>">
                    <i class="fas fa-diagram-project"></i>
                    <span class="eco-node-label"><?php i18n_e('nav.motherboards', [], 'Motherboard'); ?></span>
                    <span class="eco-node-meta">VRM+</span>
                </button>

                <button type="button" class="eco-node"
                        data-component="ram"
                        data-connects="cpu,motherboard"
                        data-label="<?php i18n_e('home.eco_ram_label', [], 'Memory (RAM)'); ?>"
                        data-desc="<?php i18n_e('home.eco_ram_desc', [], 'Ultra-low latency memory kits tuned for your specific CPU architecture.'); ?>"
                        data-cta-url="builder.php?tab=memory-finder"
                        data-cta-text="<?php i18n_e('nav.memory_finder'); ?>">
                    <i class="fas fa-memory"></i>
                    <span class="eco-node-label"><?php i18n_e('nav.memory_ram', [], 'RAM'); ?></span>
                    <span class="eco-node-meta">DDR5</span>
                </button>

                <button type="button" class="eco-node"
                        data-component="gpu"
                        data-connects="cpu,motherboard,psu"
                        data-label="<?php i18n_e('home.eco_gpu_label', [], 'Graphics (GPU)'); ?>"
                        data-desc="<?php i18n_e('home.eco_gpu_desc', [], 'The powerhouse for rendering. Optimal PCIe bandwidth so your GPU is never bottlenecked.'); ?>"
                        data-cta-url="gaming-pc-finder.php"
                        data-cta-text="<?php i18n_e('nav.gaming_pc_finder', [], 'Gaming PC Finder'); ?>">
                    <i class="fas fa-gamepad"></i>
                    <span class="eco-node-label"><?php i18n_e('nav.graphics_cards', [], 'GPU'); ?></span>
                    <span class="eco-node-meta">PCIe 5.0</span>
                </button>

                <button type="button" class="eco-node"
                        data-component="psu"
                        data-connects="motherboard,gpu"
                        data-label="<?php i18n_e('home.eco_psu_label', [], 'Power Supply'); ?>"
                        data-desc="<?php i18n_e('home.eco_psu_desc', [], 'Gold/Platinum rated power units providing clean, stable voltage with plenty of overhead.'); ?>"
                        data-cta-url="builder.php?tab=psu-calculator"
                        data-cta-text="<?php i18n_e('nav.psu_calculator'); ?>">
                    <i class="fas fa-bolt"></i>
                    <span class="eco-node-label"><?php i18n_e('nav.power_supplies', [], 'PSU'); ?></span>
                    <span class="eco-node-meta">ATX 3.1</span>
                </button>

                <button type="button" class="eco-node"
                        data-component="cooler"
                        data-connects="cpu"
                        data-label="<?php i18n_e('home.eco_cooler_label', [], 'Thermal Solutions'); ?>"
                        data-desc="<?php i18n_e('home.eco_cooler_desc', [], 'Advanced liquid and air cooling ensuring zero thermal throttling under load.'); ?>"
                        data-cta-url="products.php?category=cooling"
                        data-cta-text="<?php i18n_e('home.cat_cooling', [], 'Cooling'); ?>">
                    <i class="fas fa-fan"></i>
                    <span class="eco-node-label"><?php i18n_e('home.cat_cooling', [], 'Cooling'); ?></span>
                    <span class="eco-node-meta">240mm+</span>
                </button>
            </div>

            <div class="eco-info-panel">
                <span class="eco-panel-kicker">Maroc PC</span>
                <h3 class="eco-info-title"><?php i18n_e('home.eco_hover_prompt', [], 'Hover over a component'); ?></h3>
                <p class="eco-info-desc"><?php i18n_e('home.eco_hover_desc', [], 'Explore how each part interacts within the Maroc PC ecosystem.'); ?></p>
                <a href="builder.php" id="eco-cta-btn" class="btn btn-primary eco-cta-btn" 
                   data-default-url="builder.php"
                   data-default-text="<?php i18n_e('home.eco_build_cta', [], 'Build Your Ecosystem'); ?>"
                   >
                     <i class="fas fa-tools"></i>
                     <span id="eco-cta-text"><?php i18n_e('home.eco_build_cta', [], 'Build Your Ecosystem'); ?></span>
                </a>
            </div>
        </div>
    </div>
</section>

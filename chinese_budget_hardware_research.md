# Chinese / Ultra-Budget PC Builder Research

Date: 2026-05-15

## Recommendation

Use a conservative single-socket X99 / LGA 2011-3 Xeon path for the ultra-cheap option. Avoid dual-socket boards, mystery high-wattage PSUs, and old DDR3-only boards for the main customer-facing path.

## Parts Added

| Category | Product | Why |
| --- | --- | --- |
| CPU | Intel Xeon E5-2640 v4 | Lower 90 W TDP, 10C/20T, safer for cheap boards/coolers. |
| CPU | Intel Xeon E5-2680 v4 | Higher 14C/28T option, 120 W TDP, good multi-thread value. |
| Motherboard | HUANANZHI X99 4MF Plus DDR4 | LGA 2011-3, DDR4 ECC/non-ECC, NVMe-capable budget X99 path. |
| RAM | Kllisre DDR4 ECC Registered 16 GB | Common low-cost memory pairing for Xeon/X99 bundles. |
| GPU | MAXSUN RX 550 4 GB | Ultra-low-power display/esports option, no extra power on many RX 550 designs. |
| GPU | MLLSE RX 580 2048SP 8 GB | Cheap 1080p-low option; catalog labels it clearly as 2048SP. |
| Storage | KingSpec P3 SATA SSD 512 GB | Cheap SATA SSD path with known 512 GB tier. |
| PSU | Aigo GP550 500W 80+ Bronze | Budget PSU class; kept to 500 W for low-end builds only. |
| Cooling | Snowman M-T4 120mm Tower Cooler | Cheap tower cooler sized for the Xeon path. |

## Key Research Notes

- Intel lists the Xeon E5-2640 v4 as a 10-core / 20-thread Broadwell server CPU with 90 W TDP and DDR4 1600/1866/2133 support.
- Intel / third-party spec databases list the Xeon E5-2680 v4 as a 14-core / 28-thread LGA 2011-3 CPU around 120 W TDP.
- HUANANZHI and MACHINIST X99 boards commonly target LGA 2011-3 Xeon E5 v3/v4 CPUs, but exact RAM behavior varies by model and BIOS. The catalog keeps this path labeled as value/used-server style, not modern mainstream.
- RX 580 2048SP is not a full RX 580. TechPowerUp lists it as a 2048-shader Polaris 20 card with 150 W board power, so the product name explicitly includes `2048SP`.
- RX 550 is a safer ultra-low-power display/esports card. TechPowerUp lists RX 550 board power at 50 W.
- Cheap X99 boards have known risk areas: BIOS inconsistency, ECC/non-ECC memory quirks, VRM quality, missing manuals, and long-term support. This is why the builder path avoids dual-socket boards.

## Sources

- Intel Xeon E5-2640 v4 official specs: https://www.intel.com/content/www/us/en/products/sku/92984/intel-xeon-processor-e52640-v4-25m-cache-2-40-ghz/specifications.html
- Intel Xeon E5-2680 v4 official specs: https://www.intel.de/content/www/de/de/products/sku/91754/intel-xeon-processor-e52680-v4-35m-cache-2-40-ghz/specifications.html
- TechPowerUp RX 580 2048SP specs: https://www.techpowerup.com/gpu-specs/radeon-rx-580-2048sp.c3321
- TechPowerUp RX 550 specs: https://www.techpowerup.com/gpu-specs/radeon-rx-550.c2947
- HUANANZHI X99 board specs/examples: https://www.huananzhi.com/en/list_6/47.html
- MACHINIST X99 PR9 user manual/spec reference: https://oldrigrevive.com/wp-content/uploads/manuals/machinist/machinist_x99_pr9.pdf
- XDA cheap X99 board cautions: https://www.xda-developers.com/before-you-buy-a-cheap-x99-motherboard/
- KingSpec P3 SATA SSD family: https://www.kingspec.com/de/product/25-inch-sata-ssd-p3-series.html
- GamePower GP-550 80+ Bronze reference for budget 550 W class: https://gamepowerpc.com/power-supply/gp-550

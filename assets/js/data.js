/**
 * data.js - Single source of truth for all product data.
 * This file is updated by the admin dashboard product tools.
 * Last updated: 2026-05-18 — Added RTX 50 series, Zen 5 X3D, Arrow Lake.
 * Fixed: placeholder images, incorrect core counts, missing specs, spec inconsistencies.
 */
const products = [

    // =========================================================
    // GPUs — RTX 50 SERIES (Blackwell)
    // =========================================================
    {
        "id": 101,
        "name": "NVIDIA RTX 5090 Founders Edition",
        "brand": "NVIDIA",
        "category": "gpu",
        "price": 24999.9,
        "oldPrice": null,
        "badge": "Flagship",
        "rating": 4.9,
        "reviews": 312,
        "image": "images/products/rtx5090.png",
        "featured": true,
        "inStock": false,
        "specs": {
            "VRAM": "32 GB GDDR7",
            "CUDA Cores": "21 760",
            "Boost Clock": "2.41 GHz",
            "TDP": "575 W",
            "Architecture": "Blackwell",
            "Outputs": "3× DP 2.1 · 1× HDMI 2.1",
            "Recommended PSU": "1 000 W+"
        }
    },
    {
        "id": 102,
        "name": "NVIDIA RTX 5080 Founders Edition",
        "brand": "NVIDIA",
        "category": "gpu",
        "price": 15999.9,
        "oldPrice": null,
        "badge": "New",
        "rating": 4.8,
        "reviews": 487,
        "image": "images/products/rtx5080.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "VRAM": "16 GB GDDR7",
            "CUDA Cores": "10 752",
            "Boost Clock": "2.62 GHz",
            "TDP": "360 W",
            "Architecture": "Blackwell",
            "Outputs": "3× DP 2.1 · 1× HDMI 2.1",
            "Recommended PSU": "850 W+"
        }
    },
    {
        "id": 103,
        "name": "NVIDIA RTX 5070 Ti",
        "brand": "NVIDIA",
        "category": "gpu",
        "price": 11999.9,
        "oldPrice": null,
        "badge": "New",
        "rating": 4.8,
        "reviews": 623,
        "image": "images/products/rtx5070ti.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "VRAM": "16 GB GDDR7",
            "CUDA Cores": "8 960",
            "Boost Clock": "2.45 GHz",
            "TDP": "300 W",
            "Architecture": "Blackwell",
            "Outputs": "3× DP 2.1 · 1× HDMI 2.1",
            "Recommended PSU": "750 W+"
        }
    },
    {
        "id": 104,
        "name": "NVIDIA RTX 5070",
        "brand": "NVIDIA",
        "category": "gpu",
        "price": 7999.9,
        "oldPrice": null,
        "badge": "New",
        "rating": 4.6,
        "reviews": 891,
        "image": "images/products/rtx5070.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "VRAM": "12 GB GDDR7",
            "CUDA Cores": "6 144",
            "Boost Clock": "2.51 GHz",
            "TDP": "250 W",
            "Architecture": "Blackwell",
            "Outputs": "3× DP 2.1 · 1× HDMI 2.1",
            "Recommended PSU": "650 W+"
        }
    },
    {
        "id": 105,
        "name": "NVIDIA RTX 5060 Ti 16 GB",
        "brand": "NVIDIA",
        "category": "gpu",
        "price": 5999.9,
        "oldPrice": null,
        "badge": "New",
        "rating": 4.5,
        "reviews": 234,
        "image": "images/products/rtx5060ti.png",
        "featured": false,
        "inStock": false,
        "specs": {
            "VRAM": "16 GB GDDR7",
            "CUDA Cores": "4 608",
            "Boost Clock": "2.57 GHz",
            "TDP": "180 W",
            "Architecture": "Blackwell",
            "Outputs": "3× DP 2.1 · 1× HDMI 2.1",
            "Recommended PSU": "650 W+"
        }
    },

    // =========================================================
    // GPUs — RTX 40 SERIES (Ada Lovelace) — existing + fixes
    // =========================================================
    {
        "id": 1,
        "name": "NVIDIA RTX 4090 Founders Edition",
        "brand": "NVIDIA",
        "category": "gpu",
        "price": 13999.9,
        "oldPrice": 15999.9,
        "badge": "Sale",
        "rating": 4.9,
        "reviews": 1284,
        "image": "images/products/rtx4090.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "VRAM": "24 GB GDDR6X",
            "CUDA Cores": "16 384",
            "Boost Clock": "2.52 GHz",
            "TDP": "450 W",
            "Architecture": "Ada Lovelace",
            "Outputs": "3× DP 1.4a · 1× HDMI 2.1",
            "Recommended PSU": "850 W+"
        }
    },
    {
        "id": 17,
        "name": "NVIDIA RTX 4080 Super",
        "brand": "NVIDIA",
        "category": "gpu",
        "price": 9999.9,
        "oldPrice": 11999.9,
        "badge": "Sale",
        "rating": 4.8,
        "reviews": 706,
        "image": "images/products/rtx4080super.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "VRAM": "16 GB GDDR6X",
            "CUDA Cores": "10 240",
            "Boost Clock": "2.55 GHz",
            "TDP": "320 W",
            "Architecture": "Ada Lovelace",
            "Outputs": "3× DP 1.4a · 1× HDMI 2.1",
            "Recommended PSU": "750 W+"
        }
    },
    {
        "id": 3,
        "name": "NVIDIA RTX 4070 Ti Super",
        "brand": "NVIDIA",
        "category": "gpu",
        "price": 6999.9,
        "oldPrice": 7999.9,
        "badge": "Sale",
        "rating": 4.8,
        "reviews": 432,
        "image": "images/products/rtx4070ti.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "VRAM": "16 GB GDDR6X",
            "CUDA Cores": "8 448",
            "Boost Clock": "2.61 GHz",
            "TDP": "285 W",
            "Architecture": "Ada Lovelace",
            "Outputs": "3× DP 1.4a · 1× HDMI 2.1",
            "Recommended PSU": "700 W+"
        }
    },
    {
        "id": 2,
        "name": "AMD Radeon RX 7900 XTX",
        "brand": "AMD",
        "category": "gpu",
        "price": 7499.9,
        "oldPrice": 8999.9,
        "badge": "Sale",
        "rating": 4.7,
        "reviews": 867,
        "image": "images/products/rx7900xtx.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "VRAM": "24 GB GDDR6",
            "Stream Processors": "12 288",
            "Boost Clock": "2.50 GHz",
            "TDP": "355 W",
            "Architecture": "RDNA 3",
            "Outputs": "2× DP 2.1 · 1× HDMI 2.1",
            "Recommended PSU": "800 W+"
        }
    },
    {
        "id": 18,
        "name": "AMD Radeon RX 7800 XT",
        "brand": "AMD",
        "category": "gpu",
        "price": 4799.9,
        "oldPrice": 5799.9,
        "badge": "Sale",
        "rating": 4.6,
        "reviews": 930,
        "image": "images/products/rx7800xt.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "VRAM": "16 GB GDDR6",
            "Stream Processors": "3 840",
            "Boost Clock": "2.43 GHz",
            "TDP": "263 W",
            "Architecture": "RDNA 3",
            "Outputs": "3× DP 2.1 · 1× HDMI 2.1",
            "Recommended PSU": "650 W+"
        }
    },

    // Budget / CN GPUs
    {
        "id": 35,
        "name": "MAXSUN Radeon RX 550 4 GB",
        "brand": "MAXSUN",
        "category": "gpu",
        "price": 699.9,
        "badge": "Ultra Low",
        "rating": 4.0,
        "reviews": 176,
        "image": "images/products/rx550.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "VRAM": "4 GB GDDR5",
            "Stream Processors": "512",
            "Boost Clock": "1.18 GHz",
            "TDP": "50 W",
            "Architecture": "Polaris",
            "Outputs": "1× HDMI · 1× DVI · 1× DP",
            "Recommended PSU": "400 W+"
        }
    },
    {
        "id": 36,
        "name": "MLLSE Radeon RX 580 8 GB (2048SP)",
        "brand": "MLLSE",
        "category": "gpu",
        "price": 1299.9,
        "badge": "1080p Budget",
        "rating": 4.1,
        "reviews": 304,
        "image": "images/products/rx580.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "VRAM": "8 GB GDDR5",
            "Stream Processors": "2 048",
            "Boost Clock": "1.28 GHz",
            "TDP": "150 W",
            "Architecture": "Polaris (rebadge)",
            "Outputs": "1× HDMI · 1× DP",
            "Recommended PSU": "500 W+",
            "Warning": "2048SP variant — not full RX 580"
        }
    },

    // =========================================================
    // CPUs — AMD ZEN 5 (AM5)
    // =========================================================
    {
        "id": 201,
        "name": "AMD Ryzen 7 9800X3D",
        "brand": "AMD",
        "category": "cpu",
        "price": 4999.9,
        "oldPrice": null,
        "badge": "Best Gaming",
        "rating": 4.9,
        "reviews": 1876,
        "image": "images/products/ryzen7-9800x3d.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "Cores": "8 / 16 threads",
            "Boost Clock": "5.2 GHz",
            "L3 Cache": "96 MB (3D V-Cache)",
            "TDP": "120 W",
            "Socket": "AM5",
            "Architecture": "Zen 5",
            "Memory": "DDR5-5600"
        }
    },
    {
        "id": 202,
        "name": "AMD Ryzen 9 9950X",
        "brand": "AMD",
        "category": "cpu",
        "price": 6499.9,
        "oldPrice": null,
        "badge": "Workstation",
        "rating": 4.8,
        "reviews": 542,
        "image": "images/products/ryzen9-9950x.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Cores": "16 / 32 threads",
            "Boost Clock": "5.7 GHz",
            "L3 Cache": "64 MB",
            "TDP": "170 W",
            "Socket": "AM5",
            "Architecture": "Zen 5",
            "Memory": "DDR5-5600"
        }
    },
    {
        "id": 203,
        "name": "AMD Ryzen 7 9700X",
        "brand": "AMD",
        "category": "cpu",
        "price": 3499.9,
        "oldPrice": null,
        "badge": "Value",
        "rating": 4.7,
        "reviews": 734,
        "image": "images/products/ryzen7-9700x.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Cores": "8 / 16 threads",
            "Boost Clock": "5.5 GHz",
            "L3 Cache": "32 MB",
            "TDP": "65 W",
            "Socket": "AM5",
            "Architecture": "Zen 5",
            "Memory": "DDR5-5600"
        }
    },
    {
        "id": 204,
        "name": "AMD Ryzen 5 9600X",
        "brand": "AMD",
        "category": "cpu",
        "price": 2699.9,
        "oldPrice": null,
        "badge": "Budget AM5",
        "rating": 4.6,
        "reviews": 612,
        "image": "images/products/ryzen5-9600x.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Cores": "6 / 12 threads",
            "Boost Clock": "5.4 GHz",
            "L3 Cache": "32 MB",
            "TDP": "65 W",
            "Socket": "AM5",
            "Architecture": "Zen 5",
            "Memory": "DDR5-5600"
        }
    },

    // =========================================================
    // CPUs — AMD ZEN 4 (AM5) — existing + fixes
    // =========================================================
    {
        "id": 16,
        "name": "AMD Ryzen 7 7800X3D",
        "brand": "AMD",
        "category": "cpu",
        "price": 3799.9,
        "oldPrice": 4499.9,
        "badge": "Sale",
        "rating": 4.9,
        "reviews": 2145,
        "image": "images/products/ryzen7-7800x3d.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "Cores": "8 / 16 threads",
            "Boost Clock": "5.0 GHz",
            "L3 Cache": "96 MB (3D V-Cache)",
            "TDP": "120 W",
            "Socket": "AM5",
            "Architecture": "Zen 4",
            "Memory": "DDR5-5200"
        }
    },
    {
        "id": 5,
        "name": "AMD Ryzen 9 7950X",
        "brand": "AMD",
        "category": "cpu",
        "price": 4999.9,
        "oldPrice": 5999.9,
        "badge": "Sale",
        "rating": 4.9,
        "reviews": 1543,
        "image": "images/products/ryzen9-7950x.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Cores": "16 / 32 threads",
            "Boost Clock": "5.7 GHz",
            "L3 Cache": "64 MB",
            "TDP": "170 W",
            "Socket": "AM5",
            "Architecture": "Zen 4",
            "Memory": "DDR5-5200"
        }
    },
    {
        "id": 6,
        "name": "AMD Ryzen 7 7700X",
        "brand": "AMD",
        "category": "cpu",
        "price": 1999.9,
        "oldPrice": 2499.9,
        "badge": "Sale",
        "rating": 4.6,
        "reviews": 3200,
        "image": "images/products/ryzen7-7700x.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Cores": "8 / 16 threads",
            "Boost Clock": "5.4 GHz",
            "L3 Cache": "32 MB",
            "TDP": "105 W",
            "Socket": "AM5",
            "Architecture": "Zen 4",
            "Memory": "DDR5-5200"
        }
    },

    // =========================================================
    // CPUs — INTEL ARROW LAKE (LGA 1851)
    // =========================================================
    {
        "id": 301,
        "name": "Intel Core Ultra 9 285K",
        "brand": "Intel",
        "category": "cpu",
        "price": 5999.9,
        "oldPrice": null,
        "badge": "New",
        "rating": 4.5,
        "reviews": 412,
        "image": "images/products/core-ultra9-285k.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Cores": "24 (8P + 16E) / 24 threads",
            "Boost Clock": "5.7 GHz",
            "L3 Cache": "36 MB",
            "TDP": "125 W",
            "Socket": "LGA 1851",
            "Architecture": "Arrow Lake",
            "Memory": "DDR5-6400"
        }
    },
    {
        "id": 302,
        "name": "Intel Core Ultra 7 265K",
        "brand": "Intel",
        "category": "cpu",
        "price": 3999.9,
        "oldPrice": null,
        "badge": "New",
        "rating": 4.5,
        "reviews": 287,
        "image": "images/products/core-ultra7-265k.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Cores": "20 (8P + 12E) / 20 threads",
            "Boost Clock": "5.5 GHz",
            "L3 Cache": "30 MB",
            "TDP": "125 W",
            "Socket": "LGA 1851",
            "Architecture": "Arrow Lake",
            "Memory": "DDR5-6400"
        }
    },

    // =========================================================
    // CPUs — INTEL RAPTOR LAKE (LGA 1700) — existing + fixes
    // =========================================================
    {
        "id": 4,
        "name": "Intel Core i9-14900K",
        "brand": "Intel",
        "category": "cpu",
        "price": 4499.9,
        "oldPrice": 5499.9,
        "badge": "Sale",
        "rating": 4.8,
        "reviews": 2031,
        "image": "images/products/i9-14900k.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "Cores": "24 (8P + 16E) / 32 threads",
            "Boost Clock": "6.0 GHz",
            "L3 Cache": "36 MB",
            "TDP": "125 W",
            "Socket": "LGA 1700",
            "Architecture": "Raptor Lake Refresh",
            "Memory": "DDR4 / DDR5"
        }
    },
    {
        "id": 15,
        "name": "Intel Core i5-14600K",
        "brand": "Intel",
        "category": "cpu",
        "price": 2799.9,
        "oldPrice": 3299.9,
        "badge": "Value",
        "rating": 4.7,
        "reviews": 1180,
        "image": "images/products/i5-14600k.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Cores": "14 (6P + 8E) / 20 threads",
            "Boost Clock": "5.3 GHz",
            "L3 Cache": "24 MB",
            "TDP": "125 W",
            "Socket": "LGA 1700",
            "Architecture": "Raptor Lake Refresh",
            "Memory": "DDR4 / DDR5"
        }
    },

    // =========================================================
    // CPUs — XEON / X99 CN VALUE
    // =========================================================
    {
        "id": 31,
        "name": "Intel Xeon E5-2640 v4",
        "brand": "Intel",
        "category": "cpu",
        "price": 399.9,
        "badge": "CN Value",
        "rating": 4.2,
        "reviews": 184,
        "image": "images/products/xeon-e5.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Cores": "10 / 20 threads",
            "Boost Clock": "3.4 GHz",
            "L3 Cache": "25 MB",
            "TDP": "90 W",
            "Socket": "LGA 2011-3",
            "Architecture": "Broadwell-EP",
            "Memory": "DDR4 ECC REG",
            "Warning": "Legacy platform — no PCIe 4.0, limited overclocking"
        }
    },
    {
        "id": 32,
        "name": "Intel Xeon E5-2680 v4",
        "brand": "Intel",
        "category": "cpu",
        "price": 599.9,
        "badge": "CN Value",
        "rating": 4.3,
        "reviews": 268,
        "image": "images/products/xeon-e5.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Cores": "14 / 28 threads",
            "Boost Clock": "3.3 GHz",
            "L3 Cache": "35 MB",
            "TDP": "120 W",
            "Socket": "LGA 2011-3",
            "Architecture": "Broadwell-EP",
            "Memory": "DDR4 ECC REG",
            "Warning": "Legacy platform — no PCIe 4.0, limited overclocking"
        }
    },

    // =========================================================
    // RAM
    // =========================================================
    {
        "id": 7,
        "name": "Corsair Dominator Platinum DDR5 32 GB",
        "brand": "Corsair",
        "category": "ram",
        "price": 1899.9,
        "oldPrice": 2199.9,
        "badge": "Sale",
        "rating": 4.7,
        "reviews": 654,
        "image": "images/products/corsair-ddr5.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "Capacity": "32 GB (2 × 16 GB)",
            "Type": "DDR5",
            "Speed": "DDR5-6000",
            "Latency": "CL36",
            "Voltage": "1.35 V",
            "Profile": "XMP 3.0"
        }
    },
    {
        "id": 8,
        "name": "G.Skill Trident Z5 RGB 64 GB",
        "brand": "G.Skill",
        "category": "ram",
        "price": 2999.9,
        "badge": "New",
        "rating": 4.8,
        "reviews": 312,
        "image": "images/products/gskill-ddr5.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Capacity": "64 GB (2 × 32 GB)",
            "Type": "DDR5",
            "Speed": "DDR5-6400",
            "Latency": "CL32",
            "Voltage": "1.40 V",
            "Profile": "XMP 3.0"
        }
    },
    {
        "id": 19,
        "name": "Kingston Fury Beast DDR5 32 GB",
        "brand": "Kingston",
        "category": "ram",
        "price": 1499.9,
        "badge": "Value",
        "rating": 4.6,
        "reviews": 812,
        "image": "images/products/generic-ram.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Capacity": "32 GB (2 × 16 GB)",
            "Type": "DDR5",
            "Speed": "DDR5-5600",
            "Latency": "CL36",
            "Voltage": "1.25 V",
            "Profile": "XMP 3.0 / EXPO"
        }
    },
    {
        "id": 20,
        "name": "Crucial Pro DDR4 32 GB",
        "brand": "Crucial",
        "category": "ram",
        "price": 899.9,
        "badge": "Budget",
        "rating": 4.5,
        "reviews": 640,
        "image": "images/products/generic-ram.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Capacity": "32 GB (2 × 16 GB)",
            "Type": "DDR4",
            "Speed": "DDR4-3200",
            "Latency": "CL22",
            "Voltage": "1.20 V",
            "Profile": "XMP 2.0"
        }
    },
    {
        "id": 34,
        "name": "Kllisre DDR4 ECC Registered 16 GB",
        "brand": "Kllisre",
        "category": "ram",
        "price": 299.9,
        "badge": "CN Value",
        "rating": 4.0,
        "reviews": 211,
        "image": "images/products/generic-ram.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Capacity": "16 GB (1 × 16 GB)",
            "Type": "DDR4 ECC REG",
            "Speed": "DDR4-2133",
            "Latency": "Server JEDEC",
            "Voltage": "1.20 V",
            "Profile": "None — server memory",
            "Compatibility": "X99 / LGA 2011-3 only"
        }
    },

    // =========================================================
    // STORAGE
    // =========================================================
    {
        "id": 9,
        "name": "Samsung 990 Pro NVMe SSD 2 TB",
        "brand": "Samsung",
        "category": "storage",
        "price": 1699.9,
        "oldPrice": 1999.9,
        "badge": "Sale",
        "rating": 4.9,
        "reviews": 4871,
        "image": "images/products/samsung-990pro.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "Capacity": "2 TB",
            "Interface": "PCIe 4.0 × 4 NVMe",
            "Form Factor": "M.2 2280",
            "Seq. Read": "7 450 MB/s",
            "Seq. Write": "6 900 MB/s",
            "TBW": "1 200 TB"
        }
    },
    {
        "id": 10,
        "name": "WD Black SN850X 1 TB",
        "brand": "Western Digital",
        "category": "storage",
        "price": 1099.9,
        "rating": 4.8,
        "reviews": 2190,
        "image": "images/products/wd-sn850x.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Capacity": "1 TB",
            "Interface": "PCIe 4.0 × 4 NVMe",
            "Form Factor": "M.2 2280",
            "Seq. Read": "7 300 MB/s",
            "Seq. Write": "6 600 MB/s",
            "TBW": "600 TB"
        }
    },
    {
        "id": 21,
        "name": "Crucial T500 NVMe SSD 1 TB",
        "brand": "Crucial",
        "category": "storage",
        "price": 1099.9,
        "badge": "Value",
        "rating": 4.7,
        "reviews": 528,
        "image": "images/products/i-crucial-t500-m-2-pci-e-4-0-nvme-1tb-ct1000t500ssd5.webp",
        "featured": false,
        "inStock": true,
        "specs": {
            "Capacity": "1 TB",
            "Interface": "PCIe 4.0 × 4 NVMe",
            "Form Factor": "M.2 2280",
            "Seq. Read": "7 300 MB/s",
            "Seq. Write": "6 800 MB/s",
            "TBW": "600 TB"
        }
    },
    {
        "id": 37,
        "name": "KingSpec P3 SATA SSD 512 GB",
        "brand": "KingSpec",
        "category": "storage",
        "price": 299.9,
        "badge": "Ultra Low",
        "rating": 4.1,
        "reviews": 386,
        "image": "images/products/kingspec-sata.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Capacity": "512 GB",
            "Interface": "SATA III 6 Gb/s",
            "Form Factor": "2.5\"",
            "Seq. Read": "550 MB/s",
            "Seq. Write": "500 MB/s",
            "TBW": "240 TB"
        }
    },

    // =========================================================
    // COOLING
    // =========================================================
    {
        "id": 11,
        "name": "Noctua NH-D15 CPU Cooler",
        "brand": "Noctua",
        "category": "cooling",
        "price": 999.9,
        "rating": 4.9,
        "reviews": 6540,
        "image": "images/products/noctua-nhd15.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "Air — Dual Tower",
            "Fan Size": "2 × 140 mm",
            "Max TDP": "250 W+",
            "Noise": "24.6 dB(A)",
            "Socket Support": "AM5 / AM4 / LGA 1700 / LGA 1851"
        }
    },
    {
        "id": 22,
        "name": "be quiet! Dark Rock Pro 5",
        "brand": "be quiet!",
        "category": "cooling",
        "price": 1199.9,
        "badge": "Silent",
        "rating": 4.8,
        "reviews": 402,
        "image": "images/products/Dark-Rock-Pro-5_Promo-1024x683.webp",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "Air — Dual Tower",
            "Fan Size": "1 × 120 mm + 1 × 135 mm",
            "Max TDP": "270 W",
            "Noise": "23.3 dB(A)",
            "Socket Support": "AM5 / AM4 / LGA 1700 / LGA 1851"
        }
    },
    {
        "id": 12,
        "name": "NZXT Kraken Elite 360 AIO",
        "brand": "NZXT",
        "category": "cooling",
        "price": 2799.9,
        "oldPrice": 3199.9,
        "badge": "Sale",
        "rating": 4.6,
        "reviews": 821,
        "image": "images/products/nzxt-kraken360.png",
        "featured": true,
        "inStock": false,
        "specs": {
            "Type": "Liquid AIO",
            "Radiator": "360 mm",
            "Fans": "3 × 120 mm",
            "Display": "2.36\" LCD",
            "Socket Support": "AM5 / AM4 / LGA 1700 / LGA 1851"
        }
    },
    {
        "id": 39,
        "name": "Snowman M-T4 120mm Tower Cooler",
        "brand": "Snowman",
        "category": "cooling",
        "price": 169.9,
        "badge": "CN Value",
        "rating": 4.0,
        "reviews": 198,
        "image": "images/products/snowman-mt4.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "Air — Single Tower",
            "Fan Size": "1 × 120 mm",
            "Max TDP": "150 W",
            "Noise": "Budget PWM",
            "Socket Support": "AM4 / LGA 1700 / LGA 2011-3"
        }
    },

    // =========================================================
    // PSU
    // =========================================================
    {
        "id": 13,
        "name": "Corsair RM1000x 1000W 80+ Gold",
        "brand": "Corsair",
        "category": "psu",
        "price": 1799.9,
        "rating": 4.8,
        "reviews": 1120,
        "image": "images/products/corsair-rm1000x.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Wattage": "1 000 W",
            "Efficiency": "80+ Gold",
            "Modular": "Full",
            "Fan": "135 mm Zero RPM",
            "Connectors": "12V-2×6 (RTX 50 ready)"
        }
    },
    {
        "id": 14,
        "name": "Seasonic Focus GX-850 850W",
        "brand": "Seasonic",
        "category": "psu",
        "price": 1399.9,
        "oldPrice": 1599.9,
        "badge": "Low Stock",
        "rating": 4.7,
        "reviews": 2380,
        "image": "images/products/seasonic-gx850.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "Wattage": "850 W",
            "Efficiency": "80+ Gold",
            "Modular": "Full",
            "Fan": "120 mm Hybrid",
            "Connectors": "12V-2×6 (RTX 50 ready)"
        }
    },
    {
        "id": 23,
        "name": "Cooler Master MWE Gold 750 V2",
        "brand": "Cooler Master",
        "category": "psu",
        "price": 999.9,
        "badge": "Budget",
        "rating": 4.5,
        "reviews": 785,
        "image": "images/products/mwe-gold-750-v2-atx3-600x600.jpg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Wattage": "750 W",
            "Efficiency": "80+ Gold",
            "Modular": "Semi",
            "Fan": "120 mm"
        }
    },
    {
        "id": 24,
        "name": "Corsair RM750e 750W 80+ Gold",
        "brand": "Corsair",
        "category": "psu",
        "price": 1199.9,
        "rating": 4.6,
        "reviews": 956,
        "image": "images/products/rm750e_ac62522_75331.webp",
        "featured": false,
        "inStock": true,
        "specs": {
            "Wattage": "750 W",
            "Efficiency": "80+ Gold",
            "Modular": "Full",
            "Fan": "120 mm Zero RPM"
        }
    },
    {
        "id": 38,
        "name": "Aigo GP550 500W 80+ Bronze",
        "brand": "Aigo",
        "category": "psu",
        "price": 449.9,
        "badge": "Budget",
        "rating": 4.0,
        "reviews": 126,
        "image": "images/products/aigo-gp550.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Wattage": "500 W",
            "Efficiency": "80+ Bronze",
            "Modular": "No",
            "Fan": "120 mm"
        }
    },

    // =========================================================
    // MOTHERBOARDS — AM5
    // =========================================================
    {
        "id": 25,
        "name": "ASUS ROG Strix B650E-F Gaming WiFi",
        "brand": "ASUS",
        "category": "motherboard",
        "price": 2899.9,
        "badge": "AM5 Top",
        "rating": 4.8,
        "reviews": 364,
        "image": "images/products/generic-motherboard.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "Socket": "AM5",
            "Chipset": "B650E",
            "Memory": "DDR5",
            "Memory Slots": "4 × DIMM",
            "Form Factor": "ATX",
            "M.2 Slots": "3",
            "PCIe": "1× PCIe 5.0 x16"
        }
    },
    {
        "id": 26,
        "name": "MSI MAG B650 Tomahawk WiFi",
        "brand": "MSI",
        "category": "motherboard",
        "price": 2399.9,
        "badge": "Value",
        "rating": 4.7,
        "reviews": 512,
        "image": "images/products/mag-b650-tomahawk-wifi1.jpg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Socket": "AM5",
            "Chipset": "B650",
            "Memory": "DDR5",
            "Memory Slots": "4 × DIMM",
            "Form Factor": "ATX",
            "M.2 Slots": "3",
            "PCIe": "1× PCIe 4.0 x16"
        }
    },
    {
        "id": 29,
        "name": "ASUS TUF Gaming B650-Plus WiFi",
        "brand": "ASUS",
        "category": "motherboard",
        "price": 2199.9,
        "rating": 4.7,
        "reviews": 341,
        "image": "images/products/asus_tuf_gaming_b650_plus_wifi_1668078327_1730807.jpg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Socket": "AM5",
            "Chipset": "B650",
            "Memory": "DDR5",
            "Memory Slots": "4 × DIMM",
            "Form Factor": "ATX",
            "M.2 Slots": "3",
            "PCIe": "1× PCIe 4.0 x16"
        }
    },

    // =========================================================
    // MOTHERBOARDS — LGA 1700
    // =========================================================
    {
        "id": 27,
        "name": "Gigabyte Z790 AORUS Elite AX",
        "brand": "Gigabyte",
        "category": "motherboard",
        "price": 2799.9,
        "badge": "Gaming",
        "rating": 4.8,
        "reviews": 438,
        "image": "images/products/generic-motherboard.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "Socket": "LGA 1700",
            "Chipset": "Z790",
            "Memory": "DDR5",
            "Memory Slots": "4 × DIMM",
            "Form Factor": "ATX",
            "M.2 Slots": "4",
            "PCIe": "1× PCIe 5.0 x16"
        }
    },
    {
        "id": 28,
        "name": "MSI MAG B760 Tomahawk WiFi DDR4",
        "brand": "MSI",
        "category": "motherboard",
        "price": 1999.9,
        "badge": "Budget",
        "rating": 4.6,
        "reviews": 286,
        "image": "images/products/msi_b760tamawifid4_mag_b760_tomahawk_wifi_1746346.jpg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Socket": "LGA 1700",
            "Chipset": "B760",
            "Memory": "DDR4",
            "Memory Slots": "4 × DIMM",
            "Form Factor": "ATX",
            "M.2 Slots": "3",
            "PCIe": "1× PCIe 4.0 x16"
        }
    },
    {
        "id": 30,
        "name": "Gigabyte B760M DS3H DDR4",
        "brand": "Gigabyte",
        "category": "motherboard",
        "price": 1299.9,
        "badge": "Budget",
        "rating": 4.5,
        "reviews": 219,
        "image": "images/products/generic-motherboard.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Socket": "LGA 1700",
            "Chipset": "B760",
            "Memory": "DDR4",
            "Memory Slots": "4 × DIMM",
            "Form Factor": "Micro-ATX",
            "M.2 Slots": "2",
            "PCIe": "1× PCIe 4.0 x16"
        }
    },

    // =========================================================
    // MOTHERBOARDS — LGA 1851 (Arrow Lake)
    // =========================================================
    {
        "id": 401,
        "name": "ASUS ROG Strix Z890-F Gaming WiFi",
        "brand": "ASUS",
        "category": "motherboard",
        "price": 3499.9,
        "badge": "New",
        "rating": 4.7,
        "reviews": 187,
        "image": "images/products/generic-motherboard.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Socket": "LGA 1851",
            "Chipset": "Z890",
            "Memory": "DDR5",
            "Memory Slots": "4 × DIMM",
            "Form Factor": "ATX",
            "M.2 Slots": "5",
            "PCIe": "1× PCIe 5.0 x16"
        }
    },

    // =========================================================
    // MOTHERBOARDS — X99 / LGA 2011-3
    // =========================================================
    {
        "id": 33,
        "name": "HUANANZHI X99 4MF Plus DDR4",
        "brand": "HUANANZHI",
        "category": "motherboard",
        "price": 699.9,
        "badge": "CN X99",
        "rating": 4.1,
        "reviews": 142,
        "image": "images/products/generic-motherboard.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Socket": "LGA 2011-3",
            "Chipset": "X99 / C612-class",
            "Memory": "DDR4 ECC / non-ECC",
            "Memory Slots": "4 × DIMM",
            "Form Factor": "Micro-ATX",
            "M.2 Slots": "1",
            "PCIe": "1× PCIe 3.0 x16",
            "Warning": "CN clone board — BIOS updates unreliable"
        }
    },

    // =========================================================
    // MONITORS
    // =========================================================
    {
        "id": 40,
        "name": "ASUS TUF Gaming VG27AQ",
        "brand": "ASUS",
        "category": "monitor",
        "price": 2799.9,
        "oldPrice": 3199.9,
        "badge": "1440p",
        "rating": 4.8,
        "reviews": 1250,
        "image": "images/products/generic-monitor.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "Size": "27\"",
            "Resolution": "2560 × 1440 (QHD)",
            "Refresh Rate": "165 Hz",
            "Panel": "IPS",
            "Response Time": "1 ms (MPRT)",
            "HDR": "HDR10",
            "Adaptive Sync": "G-Sync Compatible / FreeSync"
        }
    },
    {
        "id": 41,
        "name": "AOC 24G2SP 165Hz IPS",
        "brand": "AOC",
        "category": "monitor",
        "price": 1399.9,
        "oldPrice": 1499.9,
        "badge": "Value",
        "rating": 4.6,
        "reviews": 845,
        "image": "images/products/generic-monitor.png",
        "featured": true,
        "inStock": true,
        "specs": {
            "Size": "24\"",
            "Resolution": "1920 × 1080 (FHD)",
            "Refresh Rate": "165 Hz",
            "Panel": "IPS",
            "Response Time": "1 ms (MPRT)",
            "HDR": "None",
            "Adaptive Sync": "FreeSync Premium"
        }
    },
    {
        "id": 42,
        "name": "Samsung Odyssey G9 49\"",
        "brand": "Samsung",
        "category": "monitor",
        "price": 12999.9,
        "badge": "Ultrawide",
        "rating": 4.9,
        "reviews": 310,
        "image": "images/products/generic-monitor.png",
        "featured": false,
        "inStock": true,
        "specs": {
            "Size": "49\"",
            "Resolution": "5120 × 1440 (DQHD)",
            "Refresh Rate": "240 Hz",
            "Panel": "QLED VA",
            "Response Time": "1 ms (MPRT)",
            "HDR": "HDR1000",
            "Adaptive Sync": "G-Sync Compatible / FreeSync Premium Pro",
            "Curvature": "1000R"
        }
    },

    // =========================================================
    // ACCESSORIES
    // =========================================================
    {
        "id": 601,
        "name": "Noctua NT-H1 Thermal Paste 3.5g",
        "brand": "Noctua",
        "category": "accessories",
        "price": 89.9,
        "badge": "Build Essential",
        "rating": 4.8,
        "reviews": 418,
        "image": "images/products/placeholder-cooling.svg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "Thermal paste",
            "Quantity": "3.5 g",
            "Use Case": "CPU cooler installation",
            "Conductivity": "High-performance non-conductive compound"
        }
    },
    {
        "id": 602,
        "name": "Arctic MX-6 Thermal Paste 4g",
        "brand": "Arctic",
        "category": "accessories",
        "price": 79.9,
        "badge": "Value",
        "rating": 4.7,
        "reviews": 352,
        "image": "images/products/placeholder-cooling.svg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "Thermal paste",
            "Quantity": "4 g",
            "Use Case": "CPU and GPU repaste",
            "Conductivity": "Non-conductive"
        }
    },
    {
        "id": 603,
        "name": "Thermal Grizzly Kryonaut 1g",
        "brand": "Thermal Grizzly",
        "category": "accessories",
        "price": 149.9,
        "badge": "Premium",
        "rating": 4.9,
        "reviews": 286,
        "image": "images/products/placeholder-cooling.svg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "Thermal paste",
            "Quantity": "1 g",
            "Use Case": "High-end CPU cooling",
            "Conductivity": "12.5 W/mK"
        }
    },
    {
        "id": 604,
        "name": "SATA III Data Cable 50cm",
        "brand": "Maroc PC",
        "category": "accessories",
        "price": 29.9,
        "badge": "Low Cost",
        "rating": 4.5,
        "reviews": 164,
        "image": "images/products/placeholder-storage.svg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "SATA data cable",
            "Length": "50 cm",
            "Interface": "SATA III 6 Gb/s",
            "Use Case": "2.5\" SSD / HDD installation"
        }
    },
    {
        "id": 605,
        "name": "Velcro Cable Tie Pack 20 pcs",
        "brand": "Maroc PC",
        "category": "accessories",
        "price": 39.9,
        "badge": "Cable Management",
        "rating": 4.6,
        "reviews": 231,
        "image": "images/products/placeholder-service.svg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "Reusable cable ties",
            "Quantity": "20 pcs",
            "Use Case": "Cable management",
            "Color": "Black"
        }
    },
    {
        "id": 606,
        "name": "Anti-Static Wrist Strap",
        "brand": "Maroc PC",
        "category": "accessories",
        "price": 59.9,
        "badge": "First Build",
        "rating": 4.4,
        "reviews": 119,
        "image": "images/products/placeholder-service.svg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "ESD wrist strap",
            "Cable": "Coiled grounding lead",
            "Use Case": "Safe PC assembly",
            "Fit": "Adjustable"
        }
    },
    {
        "id": 607,
        "name": "PCIe 8-pin Power Adapter",
        "brand": "Maroc PC",
        "category": "accessories",
        "price": 69.9,
        "badge": "GPU Helper",
        "rating": 4.3,
        "reviews": 97,
        "image": "images/products/placeholder-psu.svg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "PCIe power adapter",
            "Connector": "Dual 6-pin to 8-pin",
            "Use Case": "GPU power compatibility",
            "Warning": "Use only with adequate PSU headroom"
        }
    },
    {
        "id": 608,
        "name": "M.2 NVMe Aluminum Heatsink",
        "brand": "Maroc PC",
        "category": "accessories",
        "price": 99.9,
        "badge": "NVMe Cooling",
        "rating": 4.6,
        "reviews": 143,
        "image": "images/products/placeholder-storage.svg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "M.2 heatsink",
            "Form Factor": "M.2 2280",
            "Material": "Aluminum",
            "Use Case": "NVMe thermal control"
        }
    },
    {
        "id": 609,
        "name": "Arctic P12 PWM 120mm Case Fan",
        "brand": "Arctic",
        "category": "accessories",
        "price": 119.9,
        "badge": "Airflow",
        "rating": 4.7,
        "reviews": 503,
        "image": "images/products/placeholder-cooling.svg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "Case fan",
            "Size": "120 mm",
            "Connector": "4-pin PWM",
            "Use Case": "Case airflow upgrade"
        }
    },
    {
        "id": 610,
        "name": "DeepCool FK140 140mm Case Fan",
        "brand": "DeepCool",
        "category": "accessories",
        "price": 169.9,
        "badge": "Airflow",
        "rating": 4.6,
        "reviews": 188,
        "image": "images/products/placeholder-cooling.svg",
        "featured": false,
        "inStock": true,
        "specs": {
            "Type": "Case fan",
            "Size": "140 mm",
            "Connector": "4-pin PWM",
            "Use Case": "Quiet high-airflow builds"
        }
    }
];

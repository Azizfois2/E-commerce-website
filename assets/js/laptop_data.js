/**
 * laptop_data.js - Outcome-oriented curated laptop database.
 * Generated dynamically from database by export-laptops.php.
 */
const laptops = [
    {
        "id": 1,
        "name": "ASUS ROG Strix SCAR 18",
        "brand": "ASUS",
        "price": 35000,
        "oldPrice": 37999,
        "image": "images/products/rog-scar18.png",
        "usageCategory": "gaming",
        "portabilityTier": "desktop_replacement",
        "screenSize": 18,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 90,
        "weightKg": 2.7,
        "specs": {
            "CPU": "Intel Core i9-14900HX (24 Cores)",
            "RAM": "32GB DDR5 5600MHz",
            "Storage": "2TB PCIe Gen4 NVMe SSD",
            "GPU": "NVIDIA GeForce RTX 4090 16GB GDDR6 (175W)"
        },
        "inStock": true,
        "stockQuantity": 5,
        "scores": {
            "portability": 1,
            "performance": 9.6,
            "screen": 9,
            "value": 6.5
        }
    },
    {
        "id": 2,
        "name": "Lenovo ThinkPad X1 Carbon Gen 12",
        "brand": "Lenovo",
        "price": 19500,
        "oldPrice": 21999,
        "image": "images/products/x1carbon.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 14,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 57,
        "weightKg": 1.12,
        "specs": {
            "CPU": "Intel Core Ultra 7 165U (12 Cores)",
            "RAM": "32GB LPDDR5",
            "Storage": "1TB PCIe Gen4 SSD",
            "GPU": "Intel Graphics"
        },
        "inStock": true,
        "stockQuantity": 5,
        "scores": {
            "portability": 8.5,
            "performance": 7.8,
            "screen": 7,
            "value": 8.6
        }
    },
    {
        "id": 3,
        "name": "HP Pavilion Aero 13",
        "brand": "HP",
        "price": 7499,
        "oldPrice": 8499,
        "image": "images/products/pavilion13.png",
        "usageCategory": "student",
        "portabilityTier": "ultralight",
        "screenSize": 13.3,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 43,
        "weightKg": 0.99,
        "specs": {
            "CPU": "AMD Ryzen 5 7535U (6 Cores / 12 Threads)",
            "RAM": "16 GB LPDDR5 6400MHz",
            "Storage": "512 GB PCIe NVMe M.2 SSD",
            "GPU": "AMD Radeon 660M Graphics",
            "Display": "13.3\" WUXGA (1920x1200) IPS 400 nits 100% sRGB",
            "Ports": "1x USB-C 10Gbps, 2x USB-A 5Gbps, 1x HDMI 2.1, 1x Headphone/Mic"
        },
        "inStock": true,
        "stockQuantity": 15,
        "scores": {
            "portability": 9.5,
            "performance": 6.5,
            "screen": 7,
            "value": 9.9
        }
    },
    {
        "id": 4,
        "name": "Apple MacBook Pro 16\" (M3 Max)",
        "brand": "Apple",
        "price": 39999,
        "oldPrice": null,
        "image": "images/products/macbookpro16.png",
        "usageCategory": "creative",
        "portabilityTier": "standard",
        "screenSize": 16.2,
        "screenQuality": "oled",
        "gpuTier": "dedicated",
        "batteryWh": 100,
        "weightKg": 2.16,
        "specs": {
            "CPU": "Apple M3 Max Chip (16-core CPU)",
            "RAM": "48 GB Unified Memory",
            "Storage": "1 TB Superfast PCIe SSD",
            "GPU": "Apple M3 Max (40-core GPU)",
            "Display": "16.2\" Liquid Retina XDR (3456x2234) 120Hz ProMotion",
            "Ports": "3x Thunderbolt 4 (USB-C), 1x HDMI, 1x SDXC Card Slot, 1x MagSafe 3"
        },
        "inStock": true,
        "stockQuantity": 3,
        "scores": {
            "portability": 3.7,
            "performance": 9.2,
            "screen": 9.8,
            "value": 5.7
        }
    },
    {
        "id": 5,
        "name": "ASUS ROG Zephyrus G14",
        "brand": "ASUS",
        "price": 21999,
        "oldPrice": 23999,
        "image": "images/products/zephyrus-g14.png",
        "usageCategory": "gaming",
        "portabilityTier": "ultralight",
        "screenSize": 14,
        "screenQuality": "oled",
        "gpuTier": "dedicated",
        "batteryWh": 72,
        "weightKg": 1.65,
        "specs": {
            "CPU": "AMD Ryzen 9 8945HS (8 Cores / 16 Threads)",
            "RAM": "32GB LPDDR5X Dual-Channel",
            "Storage": "1TB PCIe 4.0 NVMe M.2 SSD",
            "GPU": "NVIDIA GeForce RTX 4070 8GB GDDR6 (90W)"
        },
        "inStock": true,
        "stockQuantity": 7,
        "scores": {
            "portability": 7,
            "performance": 9.6,
            "screen": 9.8,
            "value": 8.8
        }
    },
    {
        "id": 6,
        "name": "ASUS ROG Strix G16",
        "brand": "ASUS",
        "price": 18500,
        "oldPrice": 19999,
        "image": "images/products/rog-g16.png",
        "usageCategory": "gaming",
        "portabilityTier": "desktop_replacement",
        "screenSize": 16,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 90,
        "weightKg": 2.5,
        "specs": {
            "CPU": "Intel Core i7-13650HX (14 Cores)",
            "RAM": "16GB DDR5 4800MHz",
            "Storage": "1TB PCIe Gen4 NVMe SSD",
            "GPU": "NVIDIA GeForce RTX 4060 8GB GDDR6 (140W)"
        },
        "inStock": true,
        "stockQuantity": 8,
        "scores": {
            "portability": 2.8,
            "performance": 9.6,
            "screen": 9,
            "value": 9.3
        }
    },
    {
        "id": 7,
        "name": "ASUS TUF Gaming A15",
        "brand": "ASUS",
        "price": 9800,
        "oldPrice": 10999,
        "image": "images/products/tuf-a15.png",
        "usageCategory": "gaming",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 90,
        "weightKg": 2.3,
        "specs": {
            "CPU": "AMD Ryzen 7 7745HX (8 Cores / 16 Threads)",
            "RAM": "16GB DDR5",
            "Storage": "512GB PCIe NVMe SSD",
            "GPU": "NVIDIA GeForce RTX 4060 8GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 12,
        "scores": {
            "portability": 3.7,
            "performance": 9.6,
            "screen": 9,
            "value": 9.9
        }
    },
    {
        "id": 8,
        "name": "ASUS TUF Gaming F15",
        "brand": "ASUS",
        "price": 9500,
        "oldPrice": null,
        "image": "images/products/tuf-f15.png",
        "usageCategory": "gaming",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 90,
        "weightKg": 2.3,
        "specs": {
            "CPU": "Intel Core i7-12700H (14 Cores)",
            "RAM": "16GB DDR4",
            "Storage": "512GB PCIe NVMe SSD",
            "GPU": "NVIDIA GeForce RTX 3060 6GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 6,
        "scores": {
            "portability": 3.7,
            "performance": 9.6,
            "screen": 9,
            "value": 9.9
        }
    },
    {
        "id": 9,
        "name": "Lenovo Legion 5 Pro",
        "brand": "Lenovo",
        "price": 17500,
        "oldPrice": 19000,
        "image": "images/products/legion5pro.png",
        "usageCategory": "gaming",
        "portabilityTier": "desktop_replacement",
        "screenSize": 16,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 80,
        "weightKg": 2.4,
        "specs": {
            "CPU": "AMD Ryzen 7 7745HX (8 Cores / 16 Threads)",
            "RAM": "16GB DDR5 4800MHz",
            "Storage": "1TB PCIe Gen4 SSD",
            "GPU": "NVIDIA GeForce RTX 4070 8GB GDDR6 (140W)"
        },
        "inStock": true,
        "stockQuantity": 8,
        "scores": {
            "portability": 3.1,
            "performance": 9.6,
            "screen": 9,
            "value": 9.4
        }
    },
    {
        "id": 10,
        "name": "Lenovo Legion 5i",
        "brand": "Lenovo",
        "price": 13500,
        "oldPrice": 14999,
        "image": "images/products/legion5i.png",
        "usageCategory": "gaming",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 80,
        "weightKg": 2.4,
        "specs": {
            "CPU": "Intel Core i7-13700HX (16 Cores)",
            "RAM": "16GB DDR5",
            "Storage": "512GB PCIe Gen4 SSD",
            "GPU": "NVIDIA GeForce RTX 4060 8GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 10,
        "scores": {
            "portability": 3.4,
            "performance": 9.6,
            "screen": 9,
            "value": 9.9
        }
    },
    {
        "id": 11,
        "name": "Lenovo Legion Slim 5",
        "brand": "Lenovo",
        "price": 14500,
        "oldPrice": 15999,
        "image": "images/products/legion-slim5.png",
        "usageCategory": "gaming",
        "portabilityTier": "standard",
        "screenSize": 16,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 80,
        "weightKg": 2,
        "specs": {
            "CPU": "AMD Ryzen 7 7745HX (8 Cores)",
            "RAM": "16GB DDR5",
            "Storage": "512GB PCIe Gen4 SSD",
            "GPU": "NVIDIA GeForce RTX 4060 8GB GDDR6 (95W)"
        },
        "inStock": true,
        "stockQuantity": 7,
        "scores": {
            "portability": 4.3,
            "performance": 9.6,
            "screen": 9,
            "value": 9.9
        }
    },
    {
        "id": 12,
        "name": "MSI Katana 15",
        "brand": "MSI",
        "price": 10500,
        "oldPrice": 11999,
        "image": "images/products/katana15.png",
        "usageCategory": "gaming",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 53,
        "weightKg": 2.2,
        "specs": {
            "CPU": "Intel Core i7-13620H (10 Cores)",
            "RAM": "16GB DDR5",
            "Storage": "512GB PCIe NVMe SSD",
            "GPU": "NVIDIA GeForce RTX 4060 8GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 9,
        "scores": {
            "portability": 4,
            "performance": 9.6,
            "screen": 9,
            "value": 9.9
        }
    },
    {
        "id": 13,
        "name": "MSI Stealth 16 Studio",
        "brand": "MSI",
        "price": 28000,
        "oldPrice": 30999,
        "image": "images/products/stealth16.png",
        "usageCategory": "creative",
        "portabilityTier": "desktop_replacement",
        "screenSize": 16,
        "screenQuality": "oled",
        "gpuTier": "dedicated",
        "batteryWh": 99,
        "weightKg": 2.1,
        "specs": {
            "CPU": "Intel Core i9-13980HX (24 Cores)",
            "RAM": "32GB DDR5",
            "Storage": "2TB PCIe Gen4 SSD",
            "GPU": "NVIDIA GeForce RTX 4080 12GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 4,
        "scores": {
            "portability": 4,
            "performance": 9.2,
            "screen": 9.8,
            "value": 7.7
        }
    },
    {
        "id": 14,
        "name": "MSI Creator M16",
        "brand": "MSI",
        "price": 19000,
        "oldPrice": 20999,
        "image": "images/products/creator-m16.png",
        "usageCategory": "creative",
        "portabilityTier": "standard",
        "screenSize": 16,
        "screenQuality": "oled",
        "gpuTier": "dedicated",
        "batteryWh": 99,
        "weightKg": 1.9,
        "specs": {
            "CPU": "Intel Core i7-13700H (14 Cores)",
            "RAM": "32GB DDR5",
            "Storage": "1TB PCIe Gen4 SSD",
            "GPU": "NVIDIA GeForce RTX 4060 8GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 6,
        "scores": {
            "portability": 4.6,
            "performance": 9.2,
            "screen": 9.8,
            "value": 9.2
        }
    },
    {
        "id": 15,
        "name": "HP Omen 16",
        "brand": "HP",
        "price": 16500,
        "oldPrice": 18000,
        "image": "images/products/omen16.png",
        "usageCategory": "gaming",
        "portabilityTier": "desktop_replacement",
        "screenSize": 16.1,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 83,
        "weightKg": 2.4,
        "specs": {
            "CPU": "Intel Core i7-13700HX (16 Cores)",
            "RAM": "16GB DDR5",
            "Storage": "1TB PCIe Gen4 SSD",
            "GPU": "NVIDIA GeForce RTX 4070 8GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 7,
        "scores": {
            "portability": 3,
            "performance": 9.6,
            "screen": 9,
            "value": 9.6
        }
    },
    {
        "id": 16,
        "name": "HP Victus 15",
        "brand": "HP",
        "price": 9700,
        "oldPrice": 10500,
        "image": "images/products/victus15.png",
        "usageCategory": "gaming",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 70,
        "weightKg": 2.29,
        "specs": {
            "CPU": "AMD Ryzen 5 7535HS (6 Cores)",
            "RAM": "8GB DDR5",
            "Storage": "512GB PCIe SSD",
            "GPU": "NVIDIA GeForce RTX 4060 8GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 13,
        "scores": {
            "portability": 3.8,
            "performance": 9.6,
            "screen": 9,
            "value": 9.9
        }
    },
    {
        "id": 17,
        "name": "HP EliteBook 840 G10",
        "brand": "HP",
        "price": 12500,
        "oldPrice": 13999,
        "image": "images/products/elitebook840.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 14,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 51,
        "weightKg": 1.38,
        "specs": {
            "CPU": "Intel Core i5-1345U (10 Cores)",
            "RAM": "16GB DDR5",
            "Storage": "512GB PCIe Gen4 SSD",
            "GPU": "Intel Iris Xe Graphics"
        },
        "inStock": true,
        "stockQuantity": 8,
        "scores": {
            "portability": 7.8,
            "performance": 7.8,
            "screen": 7,
            "value": 9.8
        }
    },
    {
        "id": 18,
        "name": "HP EliteBook 1040 G10",
        "brand": "HP",
        "price": 18000,
        "oldPrice": 19999,
        "image": "images/products/elitebook1040.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 14,
        "screenQuality": "oled",
        "gpuTier": "integrated",
        "batteryWh": 68,
        "weightKg": 1.3,
        "specs": {
            "CPU": "Intel Core i7-1365U (10 Cores)",
            "RAM": "32GB LPDDR5",
            "Storage": "1TB PCIe Gen4 SSD",
            "GPU": "Intel Iris Xe Graphics"
        },
        "inStock": true,
        "stockQuantity": 5,
        "scores": {
            "portability": 8,
            "performance": 7.8,
            "screen": 9.8,
            "value": 9.2
        }
    },
    {
        "id": 19,
        "name": "Dell Inspiron 15",
        "brand": "Dell",
        "price": 7500,
        "oldPrice": null,
        "image": "images/products/inspiron15.png",
        "usageCategory": "student",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 54,
        "weightKg": 1.91,
        "specs": {
            "CPU": "Intel Core i5-1335U (10 Cores)",
            "RAM": "8GB DDR4",
            "Storage": "512GB PCIe SSD",
            "GPU": "Intel Iris Xe Graphics"
        },
        "inStock": true,
        "stockQuantity": 15,
        "scores": {
            "portability": 4.9,
            "performance": 6.5,
            "screen": 7,
            "value": 9.9
        }
    },
    {
        "id": 20,
        "name": "Dell Inspiron 16 Plus",
        "brand": "Dell",
        "price": 11000,
        "oldPrice": 12500,
        "image": "images/products/inspiron16plus.png",
        "usageCategory": "creative",
        "portabilityTier": "standard",
        "screenSize": 16,
        "screenQuality": "oled",
        "gpuTier": "dedicated",
        "batteryWh": 86,
        "weightKg": 2,
        "specs": {
            "CPU": "Intel Core i7-13700H (14 Cores)",
            "RAM": "16GB DDR5",
            "Storage": "512GB PCIe Gen4 SSD",
            "GPU": "NVIDIA GeForce RTX 4050 6GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 9,
        "scores": {
            "portability": 4.3,
            "performance": 9.2,
            "screen": 9.8,
            "value": 9.9
        }
    },
    {
        "id": 21,
        "name": "Dell XPS 13",
        "brand": "Dell",
        "price": 16000,
        "oldPrice": 17500,
        "image": "images/products/xps13.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 13.4,
        "screenQuality": "oled",
        "gpuTier": "integrated",
        "batteryWh": 55,
        "weightKg": 1.17,
        "specs": {
            "CPU": "Intel Core Ultra 7 155H (16 Cores)",
            "RAM": "16GB LPDDR5",
            "Storage": "512GB PCIe Gen4 SSD",
            "GPU": "Intel Arc Graphics"
        },
        "inStock": true,
        "stockQuantity": 6,
        "scores": {
            "portability": 8.9,
            "performance": 7.8,
            "screen": 9.8,
            "value": 9.5
        }
    },
    {
        "id": 22,
        "name": "Dell XPS 15",
        "brand": "Dell",
        "price": 22000,
        "oldPrice": 24999,
        "image": "images/products/xps15.png",
        "usageCategory": "creative",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "oled",
        "gpuTier": "dedicated",
        "batteryWh": 86,
        "weightKg": 1.86,
        "specs": {
            "CPU": "Intel Core i7-13700H (14 Cores)",
            "RAM": "32GB DDR5",
            "Storage": "1TB PCIe Gen4 SSD",
            "GPU": "NVIDIA GeForce RTX 4060 8GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 5,
        "scores": {
            "portability": 5,
            "performance": 9.2,
            "screen": 9.8,
            "value": 8.7
        }
    },
    {
        "id": 23,
        "name": "Dell Latitude 5540",
        "brand": "Dell",
        "price": 11500,
        "oldPrice": 12999,
        "image": "images/products/latitude5540.png",
        "usageCategory": "business",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 54,
        "weightKg": 1.73,
        "specs": {
            "CPU": "Intel Core i5-1345U (10 Cores)",
            "RAM": "16GB DDR4",
            "Storage": "512GB PCIe SSD",
            "GPU": "Intel Iris Xe Graphics"
        },
        "inStock": true,
        "stockQuantity": 8,
        "scores": {
            "portability": 5.4,
            "performance": 7.8,
            "screen": 7,
            "value": 9.9
        }
    },
    {
        "id": 24,
        "name": "Lenovo ThinkPad E14 Gen 5",
        "brand": "Lenovo",
        "price": 9500,
        "oldPrice": 10500,
        "image": "images/products/thinkpad-e14.png",
        "usageCategory": "business",
        "portabilityTier": "standard",
        "screenSize": 14,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 57,
        "weightKg": 1.64,
        "specs": {
            "CPU": "AMD Ryzen 5 7530U (6 Cores)",
            "RAM": "16GB DDR4",
            "Storage": "512GB PCIe SSD",
            "GPU": "AMD Radeon Graphics"
        },
        "inStock": true,
        "stockQuantity": 11,
        "scores": {
            "portability": 7,
            "performance": 7.8,
            "screen": 7,
            "value": 9.9
        }
    },
    {
        "id": 25,
        "name": "Lenovo IdeaPad Slim 3",
        "brand": "Lenovo",
        "price": 4500,
        "oldPrice": null,
        "image": "images/products/ideapad-slim3.png",
        "usageCategory": "student",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 38,
        "weightKg": 1.7,
        "specs": {
            "CPU": "Intel Core i3-1215U (6 Cores)",
            "RAM": "8GB DDR4",
            "Storage": "256GB SSD",
            "GPU": "Intel UHD Graphics"
        },
        "inStock": true,
        "stockQuantity": 18,
        "scores": {
            "portability": 5.5,
            "performance": 6.5,
            "screen": 7,
            "value": 9.9
        }
    },
    {
        "id": 26,
        "name": "Lenovo IdeaPad Gaming 3",
        "brand": "Lenovo",
        "price": 8500,
        "oldPrice": 9500,
        "image": "images/products/ideapad-gaming3.png",
        "usageCategory": "gaming",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 45,
        "weightKg": 2.2,
        "specs": {
            "CPU": "AMD Ryzen 5 6600H (6 Cores)",
            "RAM": "8GB DDR5",
            "Storage": "512GB PCIe SSD",
            "GPU": "NVIDIA GeForce RTX 3050 4GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 14,
        "scores": {
            "portability": 4,
            "performance": 9.6,
            "screen": 9,
            "value": 9.9
        }
    },
    {
        "id": 27,
        "name": "ASUS Vivobook 15",
        "brand": "ASUS",
        "price": 4800,
        "oldPrice": null,
        "image": "images/products/vivobook15.png",
        "usageCategory": "student",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 42,
        "weightKg": 1.8,
        "specs": {
            "CPU": "Intel Core i3-1215U (6 Cores)",
            "RAM": "8GB DDR4",
            "Storage": "256GB PCIe SSD",
            "GPU": "Intel UHD Graphics"
        },
        "inStock": true,
        "stockQuantity": 20,
        "scores": {
            "portability": 5.2,
            "performance": 6.5,
            "screen": 7,
            "value": 9.9
        }
    },
    {
        "id": 28,
        "name": "ASUS Vivobook Pro 15 OLED",
        "brand": "ASUS",
        "price": 12000,
        "oldPrice": 13500,
        "image": "images/products/vivobook-pro15.png",
        "usageCategory": "creative",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "oled",
        "gpuTier": "dedicated",
        "batteryWh": 70,
        "weightKg": 1.8,
        "specs": {
            "CPU": "AMD Ryzen 5 7535HS (6 Cores)",
            "RAM": "16GB DDR5",
            "Storage": "512GB PCIe SSD",
            "GPU": "NVIDIA GeForce RTX 3050 6GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 9,
        "scores": {
            "portability": 5.2,
            "performance": 9.2,
            "screen": 9.8,
            "value": 9.9
        }
    },
    {
        "id": 29,
        "name": "ASUS ZenBook 14 OLED",
        "brand": "ASUS",
        "price": 13500,
        "oldPrice": 14999,
        "image": "images/products/zenbook14.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 14,
        "screenQuality": "oled",
        "gpuTier": "integrated",
        "batteryWh": 75,
        "weightKg": 1.39,
        "specs": {
            "CPU": "Intel Core Ultra 5 125H (14 Cores)",
            "RAM": "16GB LPDDR5X",
            "Storage": "512GB PCIe Gen4 SSD",
            "GPU": "Intel Arc Graphics"
        },
        "inStock": true,
        "stockQuantity": 10,
        "scores": {
            "portability": 7.7,
            "performance": 7.8,
            "screen": 9.8,
            "value": 9.9
        }
    },
    {
        "id": 30,
        "name": "ASUS ZenBook S 13 OLED",
        "brand": "ASUS",
        "price": 16000,
        "oldPrice": 17500,
        "image": "images/products/zenbook-s13.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 13.3,
        "screenQuality": "oled",
        "gpuTier": "integrated",
        "batteryWh": 63,
        "weightKg": 1,
        "specs": {
            "CPU": "AMD Ryzen 7 7745U (8 Cores)",
            "RAM": "16GB LPDDR5",
            "Storage": "1TB PCIe Gen4 SSD",
            "GPU": "AMD Radeon 780M"
        },
        "inStock": true,
        "stockQuantity": 6,
        "scores": {
            "portability": 9.5,
            "performance": 7.8,
            "screen": 9.8,
            "value": 9.5
        }
    },
    {
        "id": 31,
        "name": "Acer Nitro 5",
        "brand": "Acer",
        "price": 9200,
        "oldPrice": 10500,
        "image": "images/products/nitro5.png",
        "usageCategory": "gaming",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 57,
        "weightKg": 2.5,
        "specs": {
            "CPU": "AMD Ryzen 5 7535HS (6 Cores)",
            "RAM": "8GB DDR5",
            "Storage": "512GB PCIe SSD",
            "GPU": "NVIDIA GeForce RTX 4050 6GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 14,
        "scores": {
            "portability": 3.1,
            "performance": 9.6,
            "screen": 9,
            "value": 9.9
        }
    },
    {
        "id": 32,
        "name": "Acer Nitro V 15",
        "brand": "Acer",
        "price": 8800,
        "oldPrice": 9800,
        "image": "images/products/nitro-v15.png",
        "usageCategory": "gaming",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 57,
        "weightKg": 2.4,
        "specs": {
            "CPU": "Intel Core i5-13420H (12 Cores)",
            "RAM": "8GB DDR5",
            "Storage": "512GB PCIe SSD",
            "GPU": "NVIDIA GeForce RTX 4050 6GB GDDR6"
        },
        "inStock": true,
        "stockQuantity": 11,
        "scores": {
            "portability": 3.4,
            "performance": 9.6,
            "screen": 9,
            "value": 9.9
        }
    },
    {
        "id": 33,
        "name": "Acer Aspire 5",
        "brand": "Acer",
        "price": 5200,
        "oldPrice": null,
        "image": "images/products/aspire5.png",
        "usageCategory": "student",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 50,
        "weightKg": 1.8,
        "specs": {
            "CPU": "AMD Ryzen 5 7530U (6 Cores)",
            "RAM": "8GB DDR4",
            "Storage": "256GB PCIe SSD",
            "GPU": "AMD Radeon Graphics"
        },
        "inStock": true,
        "stockQuantity": 17,
        "scores": {
            "portability": 5.2,
            "performance": 6.5,
            "screen": 7,
            "value": 9.9
        }
    },
    {
        "id": 34,
        "name": "Acer Swift Go 14",
        "brand": "Acer",
        "price": 8500,
        "oldPrice": 9500,
        "image": "images/products/swift-go14.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 14,
        "screenQuality": "oled",
        "gpuTier": "integrated",
        "batteryWh": 65,
        "weightKg": 1.4,
        "specs": {
            "CPU": "AMD Ryzen 5 7530U (6 Cores)",
            "RAM": "16GB DDR4",
            "Storage": "512GB PCIe SSD",
            "GPU": "AMD Radeon Graphics"
        },
        "inStock": true,
        "stockQuantity": 9,
        "scores": {
            "portability": 7.7,
            "performance": 7.8,
            "screen": 9.8,
            "value": 9.9
        }
    },
    {
        "id": 35,
        "name": "Acer Predator Helios 16",
        "brand": "Acer",
        "price": 22000,
        "oldPrice": 24500,
        "image": "images/products/predator-helios16.png",
        "usageCategory": "gaming",
        "portabilityTier": "desktop_replacement",
        "screenSize": 16,
        "screenQuality": "high_refresh",
        "gpuTier": "dedicated",
        "batteryWh": 90,
        "weightKg": 2.6,
        "specs": {
            "CPU": "Intel Core i9-13900HX (24 Cores)",
            "RAM": "32GB DDR5",
            "Storage": "1TB PCIe Gen4 SSD",
            "GPU": "NVIDIA GeForce RTX 4080 12GB GDDR6 (175W)"
        },
        "inStock": true,
        "stockQuantity": 4,
        "scores": {
            "portability": 2.5,
            "performance": 9.6,
            "screen": 9,
            "value": 8.7
        }
    },
    {
        "id": 36,
        "name": "HP Pavilion 15",
        "brand": "HP",
        "price": 5500,
        "oldPrice": null,
        "image": "images/products/pavilion15.png",
        "usageCategory": "student",
        "portabilityTier": "standard",
        "screenSize": 15.6,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 41,
        "weightKg": 1.75,
        "specs": {
            "CPU": "Intel Core i5-1235U (10 Cores)",
            "RAM": "8GB DDR4",
            "Storage": "256GB SSD",
            "GPU": "Intel Iris Xe Graphics"
        },
        "inStock": true,
        "stockQuantity": 16,
        "scores": {
            "portability": 5.4,
            "performance": 6.5,
            "screen": 7,
            "value": 9.9
        }
    },
    {
        "id": 37,
        "name": "HP Pavilion Plus 14",
        "brand": "HP",
        "price": 8800,
        "oldPrice": 9500,
        "image": "images/products/pavilion-plus14.png",
        "usageCategory": "student",
        "portabilityTier": "ultralight",
        "screenSize": 14,
        "screenQuality": "oled",
        "gpuTier": "integrated",
        "batteryWh": 51,
        "weightKg": 1.42,
        "specs": {
            "CPU": "Intel Core i5-13500H (12 Cores)",
            "RAM": "16GB DDR4",
            "Storage": "512GB PCIe SSD",
            "GPU": "Intel Iris Xe Graphics"
        },
        "inStock": true,
        "stockQuantity": 10,
        "scores": {
            "portability": 7.6,
            "performance": 6.5,
            "screen": 9.8,
            "value": 9.9
        }
    },
    {
        "id": 38,
        "name": "MacBook Air M3 13",
        "brand": "Apple",
        "price": 16000,
        "oldPrice": 17500,
        "image": "images/products/macbook-air-m3-13.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 13.6,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 52,
        "weightKg": 1.24,
        "specs": {
            "CPU": "Apple M3 (8-Core CPU)",
            "RAM": "16GB Unified Memory",
            "Storage": "512GB SSD",
            "GPU": "Apple M3 10-Core GPU"
        },
        "inStock": true,
        "stockQuantity": 10,
        "scores": {
            "portability": 8.5,
            "performance": 7.8,
            "screen": 7,
            "value": 9.2
        }
    },
    {
        "id": 39,
        "name": "MacBook Air M3 15",
        "brand": "Apple",
        "price": 19500,
        "oldPrice": 21000,
        "image": "images/products/macbook-air-m3-15.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 15.3,
        "screenQuality": "standard",
        "gpuTier": "integrated",
        "batteryWh": 66,
        "weightKg": 1.51,
        "specs": {
            "CPU": "Apple M3 (8-Core CPU)",
            "RAM": "16GB Unified Memory",
            "Storage": "512GB SSD",
            "GPU": "Apple M3 10-Core GPU"
        },
        "inStock": true,
        "stockQuantity": 8,
        "scores": {
            "portability": 6.3,
            "performance": 7.8,
            "screen": 7,
            "value": 8.6
        }
    },
    {
        "id": 40,
        "name": "MacBook Pro M4 14",
        "brand": "Apple",
        "price": 22000,
        "oldPrice": 23999,
        "image": "images/products/macbook-pro-m4-14.png",
        "usageCategory": "creative",
        "portabilityTier": "ultralight",
        "screenSize": 14.2,
        "screenQuality": "oled",
        "gpuTier": "integrated",
        "batteryWh": 70,
        "weightKg": 1.55,
        "specs": {
            "CPU": "Apple M4 (10-Core CPU)",
            "RAM": "16GB Unified Memory",
            "Storage": "512GB SSD",
            "GPU": "Apple M4 10-Core GPU"
        },
        "inStock": true,
        "stockQuantity": 7,
        "scores": {
            "portability": 7.1,
            "performance": 7.5,
            "screen": 9.8,
            "value": 8.4
        }
    },
    {
        "id": 41,
        "name": "MacBook Pro M4 Pro 16",
        "brand": "Apple",
        "price": 34000,
        "oldPrice": 36999,
        "image": "images/products/macbook-pro-m4-16.png",
        "usageCategory": "creative",
        "portabilityTier": "standard",
        "screenSize": 16.2,
        "screenQuality": "oled",
        "gpuTier": "integrated",
        "batteryWh": 88,
        "weightKg": 2.14,
        "specs": {
            "CPU": "Apple M4 Pro (14-Core CPU)",
            "RAM": "24GB Unified Memory",
            "Storage": "512GB SSD",
            "GPU": "Apple M4 Pro 20-Core GPU"
        },
        "inStock": true,
        "stockQuantity": 5,
        "scores": {
            "portability": 3.7,
            "performance": 7.5,
            "screen": 9.8,
            "value": 6.4
        }
    },
    {
        "id": 42,
        "name": "Samsung Galaxy Book4 Pro",
        "brand": "Samsung",
        "price": 17000,
        "oldPrice": 18500,
        "image": "images/products/galaxy-book4-pro.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 14,
        "screenQuality": "oled",
        "gpuTier": "integrated",
        "batteryWh": 63,
        "weightKg": 1.17,
        "specs": {
            "CPU": "Intel Core Ultra 7 155H (16 Cores)",
            "RAM": "16GB LPDDR5",
            "Storage": "512GB PCIe Gen4 SSD",
            "GPU": "Intel Arc Graphics"
        },
        "inStock": true,
        "stockQuantity": 6,
        "scores": {
            "portability": 8.4,
            "performance": 7.8,
            "screen": 9.8,
            "value": 9.3
        }
    },
    {
        "id": 43,
        "name": "Samsung Galaxy Book4 360",
        "brand": "Samsung",
        "price": 13500,
        "oldPrice": 14999,
        "image": "images/products/galaxy-book4-360.png",
        "usageCategory": "business",
        "portabilityTier": "ultralight",
        "screenSize": 15.6,
        "screenQuality": "oled",
        "gpuTier": "integrated",
        "batteryWh": 68,
        "weightKg": 1.59,
        "specs": {
            "CPU": "Intel Core Ultra 5 125H (14 Cores)",
            "RAM": "16GB LPDDR5",
            "Storage": "256GB PCIe SSD",
            "GPU": "Intel Arc Graphics"
        },
        "inStock": true,
        "stockQuantity": 7,
        "scores": {
            "portability": 5.9,
            "performance": 7.8,
            "screen": 9.8,
            "value": 9.9
        }
    }
];

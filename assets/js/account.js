(() => {
    'use strict';

    // ── Helpers ──────────────────────────────────────────────
    function formatMAD(value) {
        return Number(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' DH';
    }

    function showAlert(id, msg, isError) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = `<div class="alert-inline ${isError ? 'error' : 'success'}">${msg}</div>`;
        setTimeout(() => { if (el) el.innerHTML = ''; }, 5000);
    }

    const accountToastTranslations = {
        fr: {
            'Backup codes copied to clipboard!': 'Codes de secours copies dans le presse-papiers !',
            'Backup codes downloaded!': 'Codes de secours telecharges !',
            'Order cancelled.': 'Commande annulee.',
            'Order not found.': 'Commande introuvable.',
            'Failed to load order details.': 'Impossible de charger les details de commande.',
            'Failed to load orders.': 'Impossible de charger les commandes.',
            'Failed to load wishlist.': 'Impossible de charger la liste de favoris.',
            'Build link copied.': 'Lien du build copie.',
            'Build deleted.': 'Build supprime.',
            'Failed to load saved builds.': 'Impossible de charger les builds sauvegardes.',
            'Reward redeemed successfully!': 'Recompense utilisee avec succes !',
            'Failed to load loyalty data.': 'Impossible de charger les donnees de fidelite.',
            'Please enter your password.': 'Veuillez saisir votre mot de passe.',
            'Account restored!': 'Compte restaure !',
            'Enabled': 'Active',
            'Disabled': 'Desactive',
            'Cancelled': 'Annulee',
            'Pending': 'En attente',
            'Processing': 'Traitement',
            'Shipped': 'Expedie',
            'Delivered': 'Livre',
            'Payment': 'Paiement',
            'Total': 'Total',
            'Track': 'Suivre',
            'Cancel': 'Annuler',
            'Order #': 'Commande #',
            'Not set': 'Non defini',
            'No item preview': 'Aucun apercu',
            'No orders yet': 'Aucune commande pour le moment',
            'Your order history will appear here once you make a purchase.': 'Votre historique de commandes apparaitra ici apres un achat.',
            'Start Shopping': 'Commencer vos achats',
            'Sending': 'Envoi...',
            'Sending request...': 'Envoi de la demande...',
            'Done': 'Termine',
            'Failed': 'Echec',
            'Request failed.': 'La demande a echoue.',
            'Rewards Store': 'Boutique recompenses',
            'Points on purchases': 'Points sur les achats',
            'Birthday bonus': 'Bonus anniversaire',
            '1x points on purchases': '1x points sur les achats',
            '1.2x points on purchases': '1,2x points sur les achats',
            '1.5x points on purchases': '1,5x points sur les achats',
            '2x points on purchases': '2x points sur les achats',
            'Free standard shipping': 'Livraison standard gratuite',
            'Free express shipping': 'Livraison express gratuite',
            'Early access to sales': 'Acces anticipe aux promos',
            'Birthday bonus 100 pts': 'Bonus anniversaire 100 pts',
            'Birthday bonus 200 pts': 'Bonus anniversaire 200 pts',
            'Birthday bonus 300 pts': 'Bonus anniversaire 300 pts',
            'Birthday bonus 500 pts': 'Bonus anniversaire 500 pts',
            'Free Large Mousepad': 'Grand tapis de souris offert',
            'Premium extended desk mat for gaming.': 'Tapis de bureau premium etendu pour le gaming.',
            '10% Cart Discount': '10 % de remise panier',
            'Get 10% off your entire next purchase.': 'Obtenez 10 % de remise sur votre prochain achat.',
            'Free Standard Shipping': 'Livraison standard gratuite',
            'Waive the shipping fee on your next order.': 'Supprimez les frais de livraison de votre prochaine commande.',
            'Redeemed reward: {reward}': 'Recompense utilisee : {reward}',
            'Redeemed {points} points for {discount} DH discount': '{points} points utilises pour {discount} DH de remise',
            'Delete this saved build?': 'Supprimer cette config sauvegardee ?',
            'Failed to delete build.': 'Impossible de supprimer la config.',
            'Please enter your current password.': 'Veuillez saisir votre mot de passe actuel.',
            'Please enter the email verification code.': 'Veuillez saisir le code de verification par email.',
            'Send code': 'Envoyer le code',
            'Sending...': 'Envoi...',
            'Code sent. Check your email.': 'Code envoye. Verifiez votre email.',
            'Could not send verification code.': 'Impossible d envoyer le code de verification.',
            'Saving...': 'Enregistrement...',
            'Failed to update two-factor authentication.': 'Impossible de mettre a jour l authentification a deux facteurs.',
            'Confirm': 'Confirmer',
            'Enter your current password to set up an authenticator app:': 'Saisissez votre mot de passe actuel pour configurer une application d authentification :',
            'Enter the 6-digit email code to set up an authenticator app:': 'Saisissez le code email a 6 chiffres pour configurer l application d authentification :',
            'Generating...': 'Generation...',
            'Setup Authenticator': 'Configurer l authentificateur',
            'Failed to start authenticator setup.': 'Impossible de demarrer la configuration de l authentificateur.',
            'Authenticator setup failed.': 'La configuration de l authentificateur a echoue.',
            'MAROC PC TWO-FACTOR BACKUP CODES': 'CODES DE SECOURS 2FA MAROC PC',
            'Save these codes securely. Each code can be used once.': 'Conservez ces codes en securite. Chaque code ne peut etre utilise qu une fois.',
            'Please enter your current password to regenerate backup codes:': 'Saisissez votre mot de passe actuel pour regenerer les codes de secours :',
            'Enter the 6-digit email code to regenerate backup codes:': 'Saisissez le code email a 6 chiffres pour regenerer les codes de secours :',
            'Regenerate Codes': 'Regenerer les codes',
            'Failed to regenerate backup codes.': 'Impossible de regenerer les codes de secours.',
            'Password updated!': 'Mot de passe mis a jour !',
            'Update Password': 'Mettre a jour le mot de passe',
            'Cancel order #{id}? This cannot be undone.': 'Annuler la commande #{id} ? Cette action est definitive.',
            'Could not cancel order.': 'Impossible d annuler la commande.',
            'Your wishlist is empty': 'Votre liste de favoris est vide',
            'Save items you like to view them later.': 'Enregistrez les articles que vous aimez pour les revoir plus tard.',
            'Browse Products': 'Parcourir les produits',
            'View': 'Voir',
            'No saved builds yet': 'Aucune config sauvegardee',
            'Use the Builder to save, share, and price full setups.': 'Utilisez le Builder pour sauvegarder, partager et chiffrer des configurations completes.',
            'Open Builder': 'Ouvrir le Builder',
            'Copy this build link:': 'Copiez ce lien de config :',
            'Build link copied.': 'Lien du build copie.',
            'Build deleted.': 'Build supprime.',
            'Failed to load saved builds.': 'Impossible de charger les builds sauvegardes.',
            'Redeem': 'Utiliser',
            'left': 'restants',
            'Are you sure you want to redeem this reward?': 'Voulez-vous vraiment utiliser cette recompense ?',
            'Failed to redeem reward.': 'Impossible d utiliser cette recompense.',
            'No points transactions yet.': 'Aucune transaction de points pour le moment.',
            'Points History': 'Historique des points',
            'MAX TIER REACHED': 'NIVEAU MAX ATTEINT',
            '{earned} / {next} PTS TO {tier}': '{earned} / {next} PTS VERS {tier}',
            'Profile picture must be 3 MB or smaller.': 'La photo de profil doit faire 3 Mo ou moins.',
            'Choose an image before uploading.': 'Choisissez une image avant l envoi.',
            'Uploading...': 'Envoi...',
            'Profile picture updated.': 'Photo de profil mise a jour.',
            'Profile picture removed.': 'Photo de profil supprimee.',
            'Could not upload profile picture.': 'Impossible d envoyer la photo de profil.',
            'Could not remove profile picture.': 'Impossible de supprimer la photo de profil.',
            'Remove your profile picture?': 'Supprimer votre photo de profil ?',
            'Removing...': 'Suppression...',
            'Remove Photo': 'Supprimer la photo',
            'Upload': 'Envoyer',
            'Profile updated successfully!': 'Profil mis a jour avec succes !',
            'Please fill both password fields.': 'Veuillez remplir les deux champs de mot de passe.',
            'Save Changes': 'Enregistrer',
            'Deleting...': 'Suppression...',
            'Account scheduled for deletion.': 'Compte programme pour suppression.',
            'Failed to delete account.': 'Impossible de supprimer le compte.',
            'Delete Account': 'Supprimer le compte',
            'Restoring...': 'Restauration...',
            'Failed to restore.': 'Impossible de restaurer.',
            'Restore': 'Restaurer',
            'Product': 'Produit',
            'item': 'article',
            'items': 'articles',
            'ETA': 'ETA',
            'Saved Build': 'Config sauvegardee',
            'Open': 'Ouvrir',
            'Share': 'Partager',
            'Delete': 'Supprimer'
        },
        ar: {
            'Backup codes copied to clipboard!': 'تم نسخ رموز النسخ الاحتياطي.',
            'Backup codes downloaded!': 'تم تنزيل رموز النسخ الاحتياطي.',
            'Order cancelled.': 'تم إلغاء الطلب.',
            'Order not found.': 'لم يتم العثور على الطلب.',
            'Failed to load order details.': 'تعذر تحميل تفاصيل الطلب.',
            'Failed to load orders.': 'تعذر تحميل الطلبات.',
            'Failed to load wishlist.': 'تعذر تحميل المفضلة.',
            'Build link copied.': 'تم نسخ رابط التجميعة.',
            'Build deleted.': 'تم حذف التجميعة.',
            'Failed to load saved builds.': 'تعذر تحميل التجميعات المحفوظة.',
            'Reward redeemed successfully!': 'تم استبدال المكافأة بنجاح!',
            'Failed to load loyalty data.': 'تعذر تحميل بيانات الولاء.',
            'Please enter your password.': 'يرجى إدخال كلمة المرور.',
            'Account restored!': 'تمت استعادة الحساب!',
            'Enabled': 'مفعل',
            'Disabled': 'غير مفعل',
            'Cancelled': 'ملغى',
            'Pending': 'قيد الانتظار',
            'Processing': 'قيد المعالجة',
            'Shipped': 'تم الشحن',
            'Delivered': 'تم التسليم',
            'Payment': 'الدفع',
            'Total': 'الإجمالي',
            'Track': 'تتبع',
            'Cancel': 'إلغاء',
            'Order #': 'طلب #',
            'Not set': 'غير محدد',
            'No item preview': 'لا توجد معاينة',
            'No orders yet': 'لا توجد طلبات بعد',
            'Your order history will appear here once you make a purchase.': 'سيظهر سجل طلباتك هنا بعد أول عملية شراء.',
            'Start Shopping': 'ابدأ التسوق',
            'Sending': 'جار الإرسال...',
            'Sending request...': 'جار إرسال الطلب...',
            'Done': 'تم',
            'Failed': 'فشل',
            'Request failed.': 'فشل الطلب.',
            'Rewards Store': 'متجر المكافآت',
            'Points on purchases': 'نقاط على المشتريات',
            'Birthday bonus': 'مكافأة عيد الميلاد',
            '1x points on purchases': 'نقاط 1x على المشتريات',
            '1.2x points on purchases': 'نقاط 1.2x على المشتريات',
            '1.5x points on purchases': 'نقاط 1.5x على المشتريات',
            '2x points on purchases': 'نقاط 2x على المشتريات',
            'Free standard shipping': 'شحن عادي مجاني',
            'Free express shipping': 'شحن سريع مجاني',
            'Early access to sales': 'وصول مبكر للعروض',
            'Birthday bonus 100 pts': 'مكافأة عيد الميلاد 100 نقطة',
            'Birthday bonus 200 pts': 'مكافأة عيد الميلاد 200 نقطة',
            'Birthday bonus 300 pts': 'مكافأة عيد الميلاد 300 نقطة',
            'Birthday bonus 500 pts': 'مكافأة عيد الميلاد 500 نقطة',
            'Free Large Mousepad': 'ماوس باد كبير مجاني',
            'Premium extended desk mat for gaming.': 'حصيرة مكتب ممتدة ممتازة للألعاب.',
            '10% Cart Discount': 'خصم 10% على السلة',
            'Get 10% off your entire next purchase.': 'احصل على خصم 10% على طلبك القادم بالكامل.',
            'Free Standard Shipping': 'شحن عادي مجاني',
            'Waive the shipping fee on your next order.': 'احذف رسوم الشحن من طلبك القادم.',
            'Redeemed reward: {reward}': 'تم استبدال المكافأة: {reward}',
            'Redeemed {points} points for {discount} DH discount': 'تم استبدال {points} نقطة بخصم {discount} درهم',
            'Delete this saved build?': 'هل تريد حذف هذه التجميعة؟',
            'Failed to delete build.': 'تعذر حذف التجميعة.',
            'Please enter your current password.': 'يرجى إدخال كلمة المرور الحالية.',
            'Please enter the email verification code.': 'يرجى إدخال رمز التحقق عبر البريد.',
            'Send code': 'إرسال الرمز',
            'Sending...': 'جار الإرسال...',
            'Code sent. Check your email.': 'تم إرسال الرمز. تحقق من بريدك.',
            'Could not send verification code.': 'تعذر إرسال رمز التحقق.',
            'Saving...': 'جار الحفظ...',
            'Failed to update two-factor authentication.': 'تعذر تحديث المصادقة الثنائية.',
            'Confirm': 'تأكيد',
            'Enter your current password to set up an authenticator app:': 'أدخل كلمة المرور الحالية لإعداد تطبيق المصادقة:',
            'Enter the 6-digit email code to set up an authenticator app:': 'أدخل رمز البريد المكون من 6 أرقام لإعداد تطبيق المصادقة:',
            'Generating...': 'جار الإنشاء...',
            'Setup Authenticator': 'إعداد تطبيق المصادقة',
            'Failed to start authenticator setup.': 'تعذر بدء إعداد تطبيق المصادقة.',
            'Authenticator setup failed.': 'فشل إعداد تطبيق المصادقة.',
            'MAROC PC TWO-FACTOR BACKUP CODES': 'رموز النسخ الاحتياطي للمصادقة الثنائية في MAROC PC',
            'Save these codes securely. Each code can be used once.': 'احتفظ بهذه الرموز بأمان. يمكن استخدام كل رمز مرة واحدة فقط.',
            'Please enter your current password to regenerate backup codes:': 'أدخل كلمة المرور الحالية لإعادة إنشاء رموز النسخ الاحتياطي:',
            'Enter the 6-digit email code to regenerate backup codes:': 'أدخل رمز البريد المكون من 6 أرقام لإعادة إنشاء رموز النسخ الاحتياطي:',
            'Regenerate Codes': 'إعادة إنشاء الرموز',
            'Failed to regenerate backup codes.': 'تعذر إعادة إنشاء رموز النسخ الاحتياطي.',
            'Password updated!': 'تم تحديث كلمة المرور!',
            'Update Password': 'تحديث كلمة المرور',
            'Cancel order #{id}? This cannot be undone.': 'إلغاء الطلب #{id}؟ لا يمكن التراجع عن هذا الإجراء.',
            'Could not cancel order.': 'تعذر إلغاء الطلب.',
            'Your wishlist is empty': 'المفضلة فارغة',
            'Save items you like to view them later.': 'احفظ المنتجات التي تعجبك لعرضها لاحقا.',
            'Browse Products': 'تصفح المنتجات',
            'View': 'عرض',
            'No saved builds yet': 'لا توجد تجميعات محفوظة بعد',
            'Use the Builder to save, share, and price full setups.': 'استخدم أداة التجميع لحفظ ومشاركة وتسعير التجميعات الكاملة.',
            'Open Builder': 'فتح أداة التجميع',
            'Copy this build link:': 'انسخ رابط التجميعة:',
            'Build link copied.': 'تم نسخ رابط التجميعة.',
            'Build deleted.': 'تم حذف التجميعة.',
            'Failed to load saved builds.': 'تعذر تحميل التجميعات المحفوظة.',
            'Redeem': 'استبدال',
            'left': 'متبقية',
            'Are you sure you want to redeem this reward?': 'هل تريد استبدال هذه المكافأة؟',
            'Failed to redeem reward.': 'تعذر استبدال المكافأة.',
            'No points transactions yet.': 'لا توجد معاملات نقاط بعد.',
            'Points History': 'سجل النقاط',
            'MAX TIER REACHED': 'تم بلوغ أعلى مستوى',
            '{earned} / {next} PTS TO {tier}': '{earned} / {next} نقطة نحو {tier}',
            'Profile picture must be 3 MB or smaller.': 'يجب أن تكون صورة الملف الشخصي 3 ميغابايت أو أقل.',
            'Choose an image before uploading.': 'اختر صورة قبل الرفع.',
            'Uploading...': 'جار الرفع...',
            'Profile picture updated.': 'تم تحديث صورة الملف الشخصي.',
            'Profile picture removed.': 'تمت إزالة صورة الملف الشخصي.',
            'Could not upload profile picture.': 'تعذر رفع صورة الملف الشخصي.',
            'Could not remove profile picture.': 'تعذر إزالة صورة الملف الشخصي.',
            'Remove your profile picture?': 'هل تريد إزالة صورة الملف الشخصي؟',
            'Removing...': 'جار الإزالة...',
            'Remove Photo': 'إزالة الصورة',
            'Upload': 'رفع',
            'Profile updated successfully!': 'تم تحديث الملف الشخصي بنجاح!',
            'Please fill both password fields.': 'يرجى ملء حقلي كلمة المرور.',
            'Save Changes': 'حفظ التغييرات',
            'Deleting...': 'جار الحذف...',
            'Account scheduled for deletion.': 'تمت جدولة حذف الحساب.',
            'Failed to delete account.': 'تعذر حذف الحساب.',
            'Delete Account': 'حذف الحساب',
            'Restoring...': 'جار الاستعادة...',
            'Failed to restore.': 'تعذر الاستعادة.',
            'Restore': 'استعادة',
            'Product': 'منتج',
            'item': 'منتج',
            'items': 'منتجات',
            'ETA': 'الوصول المتوقع',
            'Saved Build': 'تجميعة محفوظة',
            'Open': 'فتح',
            'Share': 'مشاركة',
            'Delete': 'حذف'
        },
        es: {
            'Backup codes copied to clipboard!': 'Codigos de respaldo copiados.',
            'Backup codes downloaded!': 'Codigos de respaldo descargados.',
            'Order cancelled.': 'Pedido cancelado.',
            'Order not found.': 'Pedido no encontrado.',
            'Failed to load order details.': 'No se pudieron cargar los detalles del pedido.',
            'Failed to load orders.': 'No se pudieron cargar los pedidos.',
            'Failed to load wishlist.': 'No se pudo cargar favoritos.',
            'Build link copied.': 'Enlace del build copiado.',
            'Build deleted.': 'Build eliminado.',
            'Failed to load saved builds.': 'No se pudieron cargar los builds guardados.',
            'Reward redeemed successfully!': 'Recompensa canjeada correctamente!',
            'Failed to load loyalty data.': 'No se pudieron cargar los datos de fidelidad.',
            'Please enter your password.': 'Introduce tu contrasena.',
            'Account restored!': 'Cuenta restaurada!',
            'Enabled': 'Activado',
            'Disabled': 'Desactivado',
            'Cancelled': 'Cancelado',
            'Pending': 'Pendiente',
            'Processing': 'Procesando',
            'Shipped': 'Enviado',
            'Delivered': 'Entregado',
            'Payment': 'Pago',
            'Total': 'Total',
            'Track': 'Seguir',
            'Cancel': 'Cancelar',
            'Order #': 'Pedido #',
            'Not set': 'No definido',
            'No item preview': 'Sin vista previa',
            'No orders yet': 'Aun no hay pedidos',
            'Your order history will appear here once you make a purchase.': 'Tu historial de pedidos aparecera aqui cuando hagas una compra.',
            'Start Shopping': 'Empezar a comprar',
            'Sending': 'Enviando...',
            'Sending request...': 'Enviando solicitud...',
            'Done': 'Listo',
            'Failed': 'Error',
            'Request failed.': 'La solicitud fallo.',
            'Rewards Store': 'Tienda de recompensas',
            'Points on purchases': 'Puntos en compras',
            'Birthday bonus': 'Bonus de cumpleanos',
            '1x points on purchases': '1x puntos en compras',
            '1.2x points on purchases': '1,2x puntos en compras',
            '1.5x points on purchases': '1,5x puntos en compras',
            '2x points on purchases': '2x puntos en compras',
            'Free standard shipping': 'Envio estandar gratis',
            'Free express shipping': 'Envio express gratis',
            'Early access to sales': 'Acceso anticipado a ofertas',
            'Birthday bonus 100 pts': 'Bonus de cumpleanos 100 pts',
            'Birthday bonus 200 pts': 'Bonus de cumpleanos 200 pts',
            'Birthday bonus 300 pts': 'Bonus de cumpleanos 300 pts',
            'Birthday bonus 500 pts': 'Bonus de cumpleanos 500 pts',
            'Free Large Mousepad': 'Alfombrilla grande gratis',
            'Premium extended desk mat for gaming.': 'Alfombrilla extendida premium para gaming.',
            '10% Cart Discount': '10% de descuento en carrito',
            'Get 10% off your entire next purchase.': 'Obtienes 10% de descuento en tu proxima compra completa.',
            'Free Standard Shipping': 'Envio estandar gratis',
            'Waive the shipping fee on your next order.': 'Elimina el coste de envio de tu proximo pedido.',
            'Redeemed reward: {reward}': 'Recompensa canjeada: {reward}',
            'Redeemed {points} points for {discount} DH discount': '{points} puntos canjeados por {discount} DH de descuento',
            'Delete this saved build?': 'Eliminar este build guardado?',
            'Failed to delete build.': 'No se pudo eliminar el build.',
            'Please enter your current password.': 'Introduce tu contrasena actual.',
            'Please enter the email verification code.': 'Introduce el codigo de verificacion por correo.',
            'Send code': 'Enviar codigo',
            'Sending...': 'Enviando...',
            'Code sent. Check your email.': 'Codigo enviado. Revisa tu correo.',
            'Could not send verification code.': 'No se pudo enviar el codigo de verificacion.',
            'Saving...': 'Guardando...',
            'Failed to update two-factor authentication.': 'No se pudo actualizar la autenticacion de dos factores.',
            'Confirm': 'Confirmar',
            'Enter your current password to set up an authenticator app:': 'Introduce tu contrasena actual para configurar una app autenticadora:',
            'Enter the 6-digit email code to set up an authenticator app:': 'Introduce el codigo de 6 digitos para configurar la app autenticadora:',
            'Generating...': 'Generando...',
            'Setup Authenticator': 'Configurar autenticador',
            'Failed to start authenticator setup.': 'No se pudo iniciar la configuracion del autenticador.',
            'Authenticator setup failed.': 'No se pudo configurar el autenticador.',
            'MAROC PC TWO-FACTOR BACKUP CODES': 'CODIGOS DE RESPALDO 2FA DE MAROC PC',
            'Save these codes securely. Each code can be used once.': 'Guarda estos codigos de forma segura. Cada codigo se puede usar una sola vez.',
            'Please enter your current password to regenerate backup codes:': 'Introduce tu contrasena actual para regenerar los codigos de respaldo:',
            'Enter the 6-digit email code to regenerate backup codes:': 'Introduce el codigo de 6 digitos para regenerar los codigos de respaldo:',
            'Regenerate Codes': 'Regenerar codigos',
            'Failed to regenerate backup codes.': 'No se pudieron regenerar los codigos de respaldo.',
            'Password updated!': 'Contrasena actualizada!',
            'Update Password': 'Actualizar contrasena',
            'Cancel order #{id}? This cannot be undone.': 'Cancelar pedido #{id}? Esta accion no se puede deshacer.',
            'Could not cancel order.': 'No se pudo cancelar el pedido.',
            'Your wishlist is empty': 'Tu lista de favoritos esta vacia',
            'Save items you like to view them later.': 'Guarda articulos que te gusten para verlos luego.',
            'Browse Products': 'Ver productos',
            'View': 'Ver',
            'No saved builds yet': 'Aun no hay builds guardados',
            'Use the Builder to save, share, and price full setups.': 'Usa el Builder para guardar, compartir y calcular setups completos.',
            'Open Builder': 'Abrir Builder',
            'Copy this build link:': 'Copia este enlace del build:',
            'Build link copied.': 'Enlace del build copiado.',
            'Build deleted.': 'Build eliminado.',
            'Failed to load saved builds.': 'No se pudieron cargar los builds guardados.',
            'Redeem': 'Canjear',
            'left': 'restantes',
            'Are you sure you want to redeem this reward?': 'Seguro que quieres canjear esta recompensa?',
            'Failed to redeem reward.': 'No se pudo canjear la recompensa.',
            'No points transactions yet.': 'Todavia no hay transacciones de puntos.',
            'Points History': 'Historial de puntos',
            'MAX TIER REACHED': 'NIVEL MAXIMO ALCANZADO',
            '{earned} / {next} PTS TO {tier}': '{earned} / {next} PTS PARA {tier}',
            'Profile picture must be 3 MB or smaller.': 'La foto de perfil debe pesar 3 MB o menos.',
            'Choose an image before uploading.': 'Elige una imagen antes de subirla.',
            'Uploading...': 'Subiendo...',
            'Profile picture updated.': 'Foto de perfil actualizada.',
            'Profile picture removed.': 'Foto de perfil eliminada.',
            'Could not upload profile picture.': 'No se pudo subir la foto de perfil.',
            'Could not remove profile picture.': 'No se pudo quitar la foto de perfil.',
            'Remove your profile picture?': 'Quitar tu foto de perfil?',
            'Removing...': 'Quitando...',
            'Remove Photo': 'Quitar foto',
            'Upload': 'Subir',
            'Profile updated successfully!': 'Perfil actualizado correctamente!',
            'Please fill both password fields.': 'Completa ambos campos de contrasena.',
            'Save Changes': 'Guardar cambios',
            'Deleting...': 'Eliminando...',
            'Account scheduled for deletion.': 'Cuenta programada para eliminacion.',
            'Failed to delete account.': 'No se pudo eliminar la cuenta.',
            'Delete Account': 'Eliminar cuenta',
            'Restoring...': 'Restaurando...',
            'Failed to restore.': 'No se pudo restaurar.',
            'Restore': 'Restaurar',
            'Product': 'Producto',
            'item': 'articulo',
            'items': 'articulos',
            'ETA': 'ETA',
            'Saved Build': 'Build guardado',
            'Open': 'Abrir',
            'Share': 'Compartir',
            'Delete': 'Eliminar'
        }
    };

    function accountT(message, vars = {}) {
        const locale = (document.documentElement.lang || 'en').slice(0, 2);
        const translated = accountToastTranslations[locale]?.[message] || message;
        return translated.replace(/\{(\w+)\}/g, (match, key) => (
            Object.prototype.hasOwnProperty.call(vars, key) ? vars[key] : match
        ));
    }

    function translateLoyaltyDescription(value) {
        const text = String(value || '');
        let match = text.match(/^Redeemed reward:\s*(.+)$/i);
        if (match) {
            return accountT('Redeemed reward: {reward}', { reward: accountT(match[1]) });
        }

        match = text.match(/^Redeemed\s+(\d+)\s+points\s+for\s+([\d.]+)\s+(?:MAD|DH) discount$/i);
        if (match) {
            return accountT('Redeemed {points} points for {discount} DH discount', {
                points: match[1],
                discount: match[2],
            });
        }

        return accountT(text);
    }

    function showToast(message, type = 'info') {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toastMessage');
        const toastIcon = toast?.querySelector('i');
        if (!toast || !toastMsg) return;

        toast.className = `toast show ${type}`;
        toastMsg.style.whiteSpace = 'pre-line';
        toastMsg.textContent = accountT(message);

        if (toastIcon) {
            if (type === 'success') toastIcon.className = 'fas fa-check-circle';
            else if (type === 'error') toastIcon.className = 'fas fa-exclamation-triangle';
            else toastIcon.className = 'fas fa-info-circle';
        }

        clearTimeout(window._accountToastTimer);
        window._accountToastTimer = setTimeout(() => {
            toast.classList.remove('show');
        }, 5000);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatStatusText(status) {
        const label = String(status || 'pending')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, ch => ch.toUpperCase());
        return accountT(label);
    }

    async function apiPost(url, data) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        });
        return res.json().catch(() => ({}));
    }

    // â”€â”€ Two-factor authentication â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    const profilePictureInput = document.getElementById('profilePictureInput');
    const profilePicturePreview = document.getElementById('profilePicturePreview');
    const profileAvatarImg = document.getElementById('profileAvatarImg');
    const uploadProfilePictureBtn = document.getElementById('uploadProfilePictureBtn');
    const removeProfilePictureBtn = document.getElementById('removeProfilePictureBtn');
    const defaultProfileImage = document.querySelector('.profile-picture-card')?.dataset.defaultProfileImage || 'Images/profile/default-avatar.svg';
    let selectedProfilePicture = null;

    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', () => {
            const file = profilePictureInput.files && profilePictureInput.files[0];
            selectedProfilePicture = file || null;
            if (!file) return;
            if (file.size > 3 * 1024 * 1024) {
                selectedProfilePicture = null;
                profilePictureInput.value = '';
                showAlert('profileAlert', accountT('Profile picture must be 3 MB or smaller.'), true);
                return;
            }
            const previewUrl = URL.createObjectURL(file);
            if (profilePicturePreview) profilePicturePreview.src = previewUrl;
            if (profileAvatarImg) profileAvatarImg.src = previewUrl;
        });
    }

    if (uploadProfilePictureBtn) {
        uploadProfilePictureBtn.addEventListener('click', async () => {
            if (!selectedProfilePicture) {
                showAlert('profileAlert', accountT('Choose an image before uploading.'), true);
                profilePictureInput?.focus();
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const formData = new FormData();
            formData.append('profile_picture', selectedProfilePicture);

            uploadProfilePictureBtn.disabled = true;
            uploadProfilePictureBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${accountT('Uploading...')}`;

            try {
                const res = await fetch('api/upload-profile-picture.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    credentials: 'same-origin',
                    body: formData
                });
                const r = await res.json().catch(() => ({}));
                if (r.success) {
                    const imageUrl = `${r.image}?v=${Date.now()}`;
                    if (profilePicturePreview) profilePicturePreview.src = imageUrl;
                    if (profileAvatarImg) profileAvatarImg.src = imageUrl;
                    if (removeProfilePictureBtn) removeProfilePictureBtn.disabled = false;
                    selectedProfilePicture = null;
                    if (profilePictureInput) profilePictureInput.value = '';
                    showAlert('profileAlert', `<i class="fas fa-check-circle"></i> ${accountT('Profile picture updated.')}`, false);
                } else {
                    showAlert('profileAlert', r.error || accountT('Could not upload profile picture.'), true);
                }
            } catch (e) {
                showAlert('profileAlert', accountT('Could not upload profile picture.'), true);
            }

            uploadProfilePictureBtn.disabled = false;
            uploadProfilePictureBtn.innerHTML = `<i class="fas fa-upload"></i> ${accountT('Upload')}`;
        });
    }

    if (removeProfilePictureBtn) {
        removeProfilePictureBtn.addEventListener('click', async () => {
            if (removeProfilePictureBtn.disabled) return;
            if (!confirm(accountT('Remove your profile picture?'))) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const originalHtml = removeProfilePictureBtn.innerHTML;
            const formData = new FormData();
            formData.append('action', 'remove');

            removeProfilePictureBtn.disabled = true;
            removeProfilePictureBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${accountT('Removing...')}`;

            try {
                const res = await fetch('api/upload-profile-picture.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    credentials: 'same-origin',
                    body: formData
                });
                const r = await res.json().catch(() => ({}));
                if (r.success) {
                    const imageUrl = `${r.image || defaultProfileImage}?v=${Date.now()}`;
                    if (profilePicturePreview) profilePicturePreview.src = imageUrl;
                    if (profileAvatarImg) profileAvatarImg.src = imageUrl;
                    selectedProfilePicture = null;
                    if (profilePictureInput) profilePictureInput.value = '';
                    showAlert('profileAlert', `<i class="fas fa-check-circle"></i> ${accountT('Profile picture removed.')}`, false);
                } else {
                    removeProfilePictureBtn.disabled = false;
                    showAlert('profileAlert', r.error || accountT('Could not remove profile picture.'), true);
                }
            } catch (e) {
                removeProfilePictureBtn.disabled = false;
                showAlert('profileAlert', accountT('Could not remove profile picture.'), true);
            }

            removeProfilePictureBtn.innerHTML = originalHtml || `<i class="fas fa-trash"></i> ${accountT('Remove Photo')}`;
        });
    }

    const twoFactorToggle = document.getElementById('twoFactorToggle');
    const twoFactorConfirm = document.getElementById('twoFactorConfirm');
    const twoFactorPassword = document.getElementById('twoFactorPassword');
    const twoFactorConfirmBtn = document.getElementById('twoFactorConfirmBtn');
    const twoFactorCancelBtn = document.getElementById('twoFactorCancelBtn');
    const sendTwoFactorCodeBtn = document.getElementById('sendTwoFactorCodeBtn');
    const twoFactorCodeHint = document.getElementById('twoFactorCodeHint');
    const twoFactorStatus = document.getElementById('twoFactorStatus');
    const twoFactorMethod = document.getElementById('twoFactorMethod');
    const setupAuthenticatorBtn = document.getElementById('setupAuthenticatorBtn');
    const authenticatorSetup = document.getElementById('authenticatorSetup');
    const authenticatorQr = document.getElementById('authenticatorQr');
    const authenticatorSecret = document.getElementById('authenticatorSecret');
    const authenticatorCode = document.getElementById('authenticatorCode');
    const confirmAuthenticatorBtn = document.getElementById('confirmAuthenticatorBtn');
    let twoFactorInitialState = Boolean(twoFactorToggle?.checked);
    let twoFactorTargetState = twoFactorInitialState;
    const twoFactorConfirmMode = twoFactorConfirm?.dataset.confirmMode || 'password';
    let lastAuthenticatorPassword = '';

    function syncTwoFactorStatus(enabled) {
        if (!twoFactorStatus) return;
        twoFactorStatus.textContent = accountT(enabled ? 'Enabled' : 'Disabled');
        twoFactorStatus.classList.toggle('enabled', enabled);
    }

    function closeTwoFactorConfirm() {
        twoFactorConfirm?.classList.remove('is-open');
        if (twoFactorPassword) twoFactorPassword.value = '';
        if (twoFactorToggle) twoFactorToggle.checked = twoFactorInitialState;
    }

    if (twoFactorToggle) {
        twoFactorToggle.addEventListener('change', () => {
            twoFactorTargetState = twoFactorToggle.checked;
            twoFactorConfirm?.classList.add('is-open');
            if (twoFactorConfirmMode === 'email-code') {
                sendTwoFactorCodeBtn?.focus();
            } else {
                twoFactorPassword?.focus();
            }
        });
    }

    if (twoFactorCancelBtn) {
        twoFactorCancelBtn.addEventListener('click', closeTwoFactorConfirm);
    }

    if (twoFactorPassword) {
        twoFactorPassword.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') twoFactorConfirmBtn?.click();
        });
    }

    let currentBackupCodes = [];

    async function requestTwoFactorOwnershipCode(button) {
        const activeButton = button || sendTwoFactorCodeBtn;
        const originalHtml = activeButton?.innerHTML || '';
        if (activeButton) {
            activeButton.disabled = true;
            activeButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${accountT('Sending...')}`;
        }

        const r = await apiPost('api/two-factor.php', { action: 'request_ownership_code' });

        if (activeButton) {
            activeButton.disabled = false;
            activeButton.innerHTML = originalHtml || `<i class="fas fa-envelope"></i> ${accountT('Send code')}`;
        }

        if (r.success) {
            const debugSuffix = r.debug_code ? ` DEV: ${r.debug_code}` : '';
            if (twoFactorCodeHint) twoFactorCodeHint.textContent = `${accountT('Code sent. Check your email.')}${debugSuffix}`;
            showAlert('twoFactorAlert', `<i class="fas fa-check-circle"></i> ${r.message}${debugSuffix}`, false);
            return true;
        }

        showAlert('twoFactorAlert', r.error || accountT('Could not send verification code.'), true);
        return false;
    }

    function twoFactorAuthPayload(action) {
        const credential = (twoFactorPassword?.value || '').trim();
        if (twoFactorConfirmMode === 'email-code') {
            return { action, ownership_code: credential };
        }
        return { action, password: credential };
    }

    if (sendTwoFactorCodeBtn) {
        sendTwoFactorCodeBtn.addEventListener('click', () => requestTwoFactorOwnershipCode(sendTwoFactorCodeBtn));
    }

    function displayBackupCodes(codes) {
        const section = document.getElementById('backupCodesSection');
        const display = document.getElementById('backupCodesDisplay');
        const grid = document.getElementById('backupCodesGrid');
        if (!section || !display || !grid) return;

        currentBackupCodes = codes;
        grid.innerHTML = codes.map(c => `
            <div style="font-family:'JetBrains Mono',monospace; font-size:0.95rem; font-weight:700; color:var(--text); text-align:center; padding:10px; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:8px;">${c}</div>
        `).join('');

        section.style.display = 'block';
        display.style.display = 'block';
    }

    if (twoFactorConfirmBtn) {
        twoFactorConfirmBtn.addEventListener('click', async () => {
            const credential = (twoFactorPassword?.value || '').trim();
            if (!credential) {
                showAlert('twoFactorAlert', twoFactorConfirmMode === 'email-code' ? accountT('Please enter the email verification code.') : accountT('Please enter your current password.'), true);
                twoFactorPassword?.focus();
                return;
            }

            twoFactorConfirmBtn.disabled = true;
            twoFactorConfirmBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${accountT('Saving...')}`;

            const r = await apiPost('api/two-factor.php', {
                ...twoFactorAuthPayload(twoFactorTargetState ? 'enable' : 'disable'),
                method: twoFactorMethod?.value || 'email'
            });

            if (r.success) {
                twoFactorInitialState = Boolean(r.enabled);
                if (twoFactorToggle) twoFactorToggle.checked = twoFactorInitialState;
                syncTwoFactorStatus(twoFactorInitialState);
                twoFactorConfirm?.classList.remove('is-open');
                if (twoFactorPassword) twoFactorPassword.value = '';
                showAlert('twoFactorAlert', `<i class="fas fa-check-circle"></i> ${r.message}`, false);
                
                if (r.backup_codes && r.backup_codes.length > 0) {
                    displayBackupCodes(r.backup_codes);
                } else if (!twoFactorInitialState) {
                    const section = document.getElementById('backupCodesSection');
                    if (section) section.style.display = 'none';
                }
            } else {
                if (twoFactorToggle) twoFactorToggle.checked = twoFactorInitialState;
                showAlert('twoFactorAlert', r.error || accountT('Failed to update two-factor authentication.'), true);
            }

            twoFactorConfirmBtn.disabled = false;
            twoFactorConfirmBtn.innerHTML = `<i class="fas fa-shield-halved"></i> ${accountT('Confirm')}`;
        });
    }

    if (setupAuthenticatorBtn) {
        setupAuthenticatorBtn.addEventListener('click', async () => {
            let payload;
            if (twoFactorConfirmMode === 'email-code') {
                const sent = await requestTwoFactorOwnershipCode(setupAuthenticatorBtn);
                if (!sent) return;
                const code = prompt(accountT('Enter the 6-digit email code to set up an authenticator app:'));
                if (!code) return;
                payload = { action: 'setup_totp', ownership_code: String(code).replace(/\D+/g, '') };
            } else {
                const password = prompt(accountT('Enter your current password to set up an authenticator app:'));
                if (!password) return;
                payload = { action: 'setup_totp', password };
                lastAuthenticatorPassword = password;
            }
            setupAuthenticatorBtn.disabled = true;
            setupAuthenticatorBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${accountT('Generating...')}`;
            const r = await apiPost('api/two-factor.php', payload);
            setupAuthenticatorBtn.disabled = false;
            setupAuthenticatorBtn.innerHTML = `<i class="fas fa-qrcode"></i> ${accountT('Setup Authenticator')}`;
            if (!r.success) {
                showAlert('twoFactorAlert', r.error || accountT('Failed to start authenticator setup.'), true);
                return;
            }
            if (authenticatorQr) authenticatorQr.src = r.qr_url;
            if (authenticatorSecret) authenticatorSecret.textContent = r.secret;
            authenticatorSetup?.classList.add('is-open');
            authenticatorCode?.focus();
        });
    }

    if (confirmAuthenticatorBtn) {
        confirmAuthenticatorBtn.addEventListener('click', async () => {
            const code = authenticatorCode?.value.trim() || '';
            if (!/^\d{6}$/.test(code)) {
                showAlert('twoFactorAlert', 'Enter the 6-digit code from your authenticator app.', true);
                return;
            }
            confirmAuthenticatorBtn.disabled = true;
            confirmAuthenticatorBtn.textContent = 'Confirming...';
            const r = await apiPost('api/two-factor.php', {
                action: 'confirm_totp',
                password: lastAuthenticatorPassword,
                code
            });
            confirmAuthenticatorBtn.disabled = false;
            confirmAuthenticatorBtn.textContent = 'Confirm Authenticator';
            if (r.success) {
                twoFactorInitialState = true;
                if (twoFactorToggle) twoFactorToggle.checked = true;
                if (twoFactorMethod) twoFactorMethod.value = 'authenticator';
                syncTwoFactorStatus(true);
                authenticatorSetup?.classList.remove('is-open');
                showAlert('twoFactorAlert', `<i class="fas fa-check-circle"></i> ${r.message}`, false);
                
                if (r.backup_codes && r.backup_codes.length > 0) {
                    displayBackupCodes(r.backup_codes);
                }
            } else {
                showAlert('twoFactorAlert', r.error || accountT('Authenticator setup failed.'), true);
            }
        });
    }

    const copyBtn = document.getElementById('copyBackupCodesBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', async () => {
            if (currentBackupCodes.length === 0) return;
            const text = currentBackupCodes.join('\n');
            try {
                await navigator.clipboard.writeText(text);
                showToast('Backup codes copied to clipboard!', 'success');
            } catch (err) {
                const tempTextarea = document.createElement('textarea');
                tempTextarea.value = text;
                document.body.appendChild(tempTextarea);
                tempTextarea.select();
                document.execCommand('copy');
                document.body.removeChild(tempTextarea);
                showToast('Backup codes copied to clipboard!', 'success');
            }
        });
    }

    const downloadBtn = document.getElementById('downloadBackupCodesBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', () => {
            if (currentBackupCodes.length === 0) return;
            const text = `${accountT('MAROC PC TWO-FACTOR BACKUP CODES')}\n\n${accountT('Save these codes securely. Each code can be used once.')}\n\n` + currentBackupCodes.join('\n');
            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'marocpc-2fa-backup-codes.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Backup codes downloaded!', 'success');
        });
    }

    const regenerateBtn = document.getElementById('regenerateBackupCodesBtn');
    if (regenerateBtn) {
        regenerateBtn.addEventListener('click', async () => {
            let payload;
            if (twoFactorConfirmMode === 'email-code') {
                const sent = await requestTwoFactorOwnershipCode(regenerateBtn);
                if (!sent) return;
                const code = prompt(accountT('Enter the 6-digit email code to regenerate backup codes:'));
                if (!code) return;
                payload = { action: 'regenerate_backup_codes', ownership_code: String(code).replace(/\D+/g, '') };
            } else {
                const password = prompt(accountT('Please enter your current password to regenerate backup codes:'));
                if (!password) return;
                payload = { action: 'regenerate_backup_codes', password };
            }

            regenerateBtn.disabled = true;
            regenerateBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${accountT('Generating...')}`;

            const r = await apiPost('api/two-factor.php', payload);

            regenerateBtn.disabled = false;
            regenerateBtn.innerHTML = `<i class="fas fa-arrows-rotate"></i> ${accountT('Regenerate Codes')}`;

            if (r.success && r.backup_codes) {
                displayBackupCodes(r.backup_codes);
                showAlert('twoFactorAlert', `<i class="fas fa-check-circle"></i> ${r.message}`, false);
            } else {
                showAlert('twoFactorAlert', r.error || accountT('Failed to regenerate backup codes.'), true);
            }
        });
    }

    // ── Profile save ─────────────────────────────────────────
    const saveBtn = document.getElementById('saveProfileBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${accountT('Saving...')}`;

            const data = {
                nom: document.getElementById('accName')?.value.trim(),
                email: document.getElementById('accEmail')?.value.trim(),
                adresse: document.getElementById('accAddress')?.value.trim(),
                telephone: document.getElementById('accPhone')?.value.trim(),
                date_naissance: document.getElementById('accDob')?.value || ''
            };
            const r = await apiPost('api/update-profile.php', data);

            if (r.success) {
                showAlert('profileAlert', `<i class="fas fa-check-circle"></i> ${accountT('Profile updated successfully!')}`, false);
            } else if (r.errors) {
                showAlert('profileAlert', Object.values(r.errors).join(' '), true);
            } else {
                showAlert('profileAlert', r.error || 'Failed to update.', true);
            }

            saveBtn.disabled = false;
            saveBtn.innerHTML = `<i class="fas fa-check"></i> ${accountT('Save Changes')}`;
        });
    }

    // ── Password change ──────────────────────────────────────
    const passBtn = document.getElementById('changePassBtn');
    if (passBtn) {
        passBtn.addEventListener('click', async () => {
            const cur = document.getElementById('accCurrentPass')?.value;
            const neu = document.getElementById('accNewPass')?.value;
            if (!cur || !neu) { showAlert('passAlert', accountT('Please fill both password fields.'), true); return; }

            passBtn.disabled = true;
            passBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            const data = {
                nom: document.getElementById('accName')?.value.trim() || '',
                email: document.getElementById('accEmail')?.value.trim() || '',
                adresse: document.getElementById('accAddress')?.value.trim() || '',
                telephone: document.getElementById('accPhone')?.value.trim() || '',
                date_naissance: document.getElementById('accDob')?.value || '',
                current_password: cur,
                new_password: neu
            };
            const r = await apiPost('api/update-profile.php', data);

            if (r.success) {
                showAlert('passAlert', `<i class="fas fa-check-circle"></i> ${accountT('Password updated!')}`, false);
                document.getElementById('accCurrentPass').value = '';
                document.getElementById('accNewPass').value = '';
            } else if (r.errors) {
                showAlert('passAlert', Object.values(r.errors).join(' '), true);
            } else {
                showAlert('passAlert', r.error || 'Failed to update.', true);
            }

            passBtn.disabled = false;
            passBtn.innerHTML = `<i class="fas fa-key"></i> ${accountT('Update Password')}`;
        });
    }

    // ── Orders ───────────────────────────────────────────────
    function statusClass(s) {
        if (s === 'delivered' || s === 'shipped') return 'status-good';
        if (s === 'cancelled') return 'status-danger';
        return 'status-warn';
    }

    async function cancelOrder(orderId, cardEl) {
        if (!confirm(accountT('Cancel order #{id}? This cannot be undone.', { id: orderId }))) return;

        const btn = cardEl.querySelector('.btn-cancel-order');
        if (btn) btn.disabled = true;

        const r = await apiPost('api/cancel-order.php', { order_id: orderId });

        if (r.success) {
            const badge = cardEl.querySelector('.order-status');
            if (badge) {
                badge.textContent = accountT('Cancelled');
                badge.className = 'order-status status-danger';
            }
            if (btn) btn.remove();
            showToast('Order cancelled.', 'success');
        } else {
            if (btn) btn.disabled = false;
            showToast(r.error || accountT('Could not cancel order.'), 'error');
        }
    }

    window.viewOrder = async function (id) {
        try {
            const res = await fetch(`api/order-detail.php?id=${id}`, { credentials: 'same-origin' });
            const data = await res.json();

            if (!data.order) {
                showToast('Order not found.', 'error');
                return;
            }

            const o = data.order;
            const items = (data.items || []);
            const history = (data.history || []);

            // Elements
            const modal = document.getElementById('trackingModalBackdrop');
            if (!modal) return;
            
            document.getElementById('trackingOrderId').innerText = `${accountT('Order #')}${o.id} - ${formatStatusText(o.status)}`;
            document.getElementById('trackingEstimatedDelivery').innerText = o.estimated_delivery ? new Date(o.estimated_delivery).toLocaleDateString(document.documentElement.lang || undefined) : 'N/A';
            document.getElementById('trackingTotalCost').innerText = formatMAD(parseFloat(o.total));

            // Items List
            document.getElementById('trackingItemsList').innerHTML = items.map(i => 
                `<div class="tracking-item">
                    <span class="tracking-item-name">${i.quantity}x ${i.name_at_time || accountT('Product')}</span>
                    <span class="tracking-item-price">${formatMAD(parseFloat(i.price_at_time))}</span>
                </div>`
            ).join('');

            // Progress Bar
            const statuses = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
            let currentIdx = statuses.indexOf(o.status);
            if (o.status === 'cancelled') currentIdx = -1;

            statuses.forEach((st, idx) => {
                const el = document.getElementById(`step-${st}`);
                if (!el) return;
                el.classList.remove('active', 'completed');
                if (o.status === 'cancelled') {
                    el.style.borderColor = 'var(--red)';
                    el.style.color = 'var(--red)';
                } else {
                    el.style.borderColor = ''; el.style.color = '';
                    if (idx < currentIdx) el.classList.add('completed');
                    else if (idx === currentIdx) el.classList.add('active');
                }
            });

            // Assembly Tracker Logic
            const assemblyContainer = document.getElementById('trackingAssemblyContainer');
            const assemblyFill = document.getElementById('trackingAssemblyFill');
            const assemblyGuideLink = document.getElementById('assemblyGuideLink');

            if (o.assembly_status && o.assembly_status !== 'not_applicable') {
                if (assemblyContainer) assemblyContainer.style.display = 'block';
                if (assemblyGuideLink) assemblyGuideLink.href = `assembly-guide.php?id=${o.id}`;

                const aStatuses = ['gathering_parts', 'building', 'testing', 'qc_passed', 'ready'];
                let aIdx = aStatuses.indexOf(o.assembly_status);

                aStatuses.forEach((st, idx) => {
                    const el = document.getElementById(`step-assembly-${st}`);
                    if (!el) return;
                    el.classList.remove('active', 'completed');
                    if (idx < aIdx) el.classList.add('completed');
                    else if (idx === aIdx) el.classList.add('active');
                });

                if (assemblyFill) {
                    assemblyFill.style.width = aIdx > 0 ? `${(aIdx / (aStatuses.length - 1)) * 100}%` : '0%';
                }
            } else {
                if (assemblyContainer) assemblyContainer.style.display = 'none';
            }

            const fill = document.getElementById('trackingProgressFill');
            const mapProgress = document.getElementById('mapRouteProgress');
            const cities = ['city-casa', 'city-rabat', 'city-tanger', 'city-dest'];
            
            // Reset map
            cities.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.remove('active', 'reached');
            });

            if (o.status === 'cancelled') {
                fill.style.width = '100%';
                fill.style.background = 'var(--red)';
                if (mapProgress) mapProgress.style.width = '0%';
            } else {
                fill.style.background = 'var(--cyan)';
                fill.style.width = currentIdx > 0 ? `${(currentIdx / (statuses.length - 1)) * 100}%` : '0%';
                
                // Map Animation Logic
                if (mapProgress) {
                    let mapPct = 0;
                    if (currentIdx === 1) { // processing -> Casa
                        mapPct = 0; 
                        document.getElementById('city-casa').classList.add('active');
                    } else if (currentIdx === 2) { // shipped -> Rabat
                        mapPct = 33;
                        document.getElementById('city-casa').classList.add('reached');
                        document.getElementById('city-rabat').classList.add('active');
                    } else if (currentIdx === 3) { // out_for_delivery -> Tanger
                        mapPct = 66;
                        document.getElementById('city-casa').classList.add('reached');
                        document.getElementById('city-rabat').classList.add('reached');
                        document.getElementById('city-tanger').classList.add('active');
                    } else if (currentIdx === 4) { // delivered -> Destination
                        mapPct = 100;
                        cities.forEach(id => document.getElementById(id).classList.add('reached'));
                        document.getElementById('city-dest').classList.add('active');
                    }
                    mapProgress.style.width = `${mapPct}%`;
                }
            }

            // Timeline
            const timelineContainer = document.getElementById('trackingTimeline');
            if (history.length > 0) {
                timelineContainer.innerHTML = history.map(h => {
                    const date = new Date(h.changed_at).toLocaleString();
                    const stName = h.new_status.replace(/_/g, ' ').toUpperCase();
                    return `
                        <div class="timeline-event">
                            <div class="timeline-date">${date}</div>
                            <div class="timeline-status">${stName}</div>
                            ${h.notes ? `<div class="timeline-notes">${h.notes}</div>` : ''}
                        </div>`;
                }).join('');
            } else {
                timelineContainer.innerHTML = `
                    <div class="timeline-event">
                        <div class="timeline-date">${new Date(o.created_at).toLocaleString()}</div>
                        <div class="timeline-status">${o.status.replace(/_/g, ' ').toUpperCase()}</div>
                    </div>`;
            }

            modal.classList.add('is-open');

        } catch (e) {
            showToast('Failed to load order details.', 'error');
        }
    };

    // Modal Close logic
    const trackModal = document.getElementById('trackingModalBackdrop');
    if (trackModal) {
        document.getElementById('trackingModalClose').addEventListener('click', () => {
            trackModal.classList.remove('is-open');
        });
        trackModal.addEventListener('click', (e) => {
            if (e.target === trackModal) trackModal.classList.remove('is-open');
        });
    }

    async function loadOrders() {
        const c = document.getElementById('ordersContainer');
        if (!c) return;

        try {
            const res = await fetch('api/orders.php', { credentials: 'same-origin' });
            const data = await res.json();

            if (!data.orders || !data.orders.length) {
                c.innerHTML = `
                    <div style="text-align:center;padding:48px 20px;">
                        <i class="fas fa-box-open" style="font-size:3rem;color:var(--muted);margin-bottom:16px;display:block;"></i>
                        <p class="no-orders" style="font-size:1.1rem;">${accountT('No orders yet')}</p>
                        <p style="color:var(--muted);font-size:0.88rem;margin-top:6px;">${accountT('Your order history will appear here once you make a purchase.')}</p>
                        <a href="products.php" style="display:inline-block;margin-top:20px;padding:12px 28px;background:var(--cyan);color:#000;border-radius:10px;font-weight:700;text-decoration:none;transition:all 0.2s;">
                            <i class="fas fa-shopping-bag"></i> ${accountT('Start Shopping')}
                        </a>
                    </div>`;
                return;
            }

            const steps = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
            c.innerHTML = '<div class="orders-list modern-orders-list">' + data.orders.map(o => {
                const status = o.status || 'pending';
                const cancellable = ['pending', 'processing'].includes(status);
                const currentIndex = steps.indexOf(status);
                const items = String(o.items_preview || '')
                    .split('||')
                    .filter(Boolean)
                    .slice(0, 4)
                    .map(raw => {
                        const [name, image, quantity] = raw.split('@@');
                        return {
                            name: name || accountT('Product'),
                            image: image || 'Images/products/placeholder-storage.svg',
                            quantity: quantity || '1'
                        };
                    });
                const overflowCount = Math.max(0, Number(o.item_count || 0) - items.length);
                const itemHtml = items.length
                    ? items.map(item => `
                        <div class="order-product-chip" title="${escapeHtml(item.name)}">
                            <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" onerror="this.src='Images/products/placeholder-storage.svg'">
                            <span>${escapeHtml(item.quantity)}x</span>
                        </div>`).join('') + (overflowCount ? `<div class="order-product-more">+${overflowCount}</div>` : '')
                    : `<div class="order-product-empty">${accountT('No item preview')}</div>`;
                const progressHtml = status === 'cancelled'
                    ? `<div class="order-progress cancelled"><span>${accountT('Cancelled')}</span></div>`
                    : `<div class="order-progress">${steps.map((step, idx) => `
                        <span class="${idx < currentIndex ? 'done' : (idx === currentIndex ? 'active' : '')}" title="${formatStatusText(step)}"></span>
                    `).join('')}</div>`;
                const deliveryText = o.estimated_delivery
                    ? new Date(o.estimated_delivery).toLocaleDateString(document.documentElement.lang || undefined, { month: 'short', day: 'numeric' })
                    : accountT('Not set');

                return `
                    <div class="order-card modern-order-card" data-order-id="${o.id}">
                        <div class="order-card-main">
                            <div>
                                <div class="order-id">${accountT('Order #')}${o.id}</div>
                                <div class="order-date">${new Date(o.created_at).toLocaleDateString(document.documentElement.lang || undefined, {
                                    year: 'numeric', month: 'short', day: 'numeric'
                                })} · ${Number(o.item_count || 0)} ${accountT(Number(o.item_count || 0) === 1 ? 'item' : 'items')}</div>
                            </div>
                            <div class="order-status ${statusClass(status)}">${formatStatusText(status)}</div>
                        </div>
                        <div class="order-products-strip">${itemHtml}</div>
                        ${progressHtml}
                        <div class="order-meta-grid">
                            <div><span>${accountT('Total')}</span><strong>${formatMAD(parseFloat(o.total))}</strong></div>
                            <div><span>${accountT('Payment')}</span><strong>${formatStatusText(o.payment_status || 'pending')}</strong></div>
                            <div><span>${accountT('ETA')}</span><strong>${deliveryText}</strong></div>
                        </div>
                        <div class="order-card-actions">
                            <button class="btn-view" onclick="viewOrder(${o.id})"><i class="fas fa-location-dot"></i> ${accountT('Track')}</button>
                            ${cancellable ? `<button class="btn-cancel-order" data-order-id="${o.id}"><i class="fas fa-times"></i> ${accountT('Cancel')}</button>` : ''}
                        </div>
                    </div>`;
            }).join('') + '</div>';

            c.querySelectorAll('.btn-cancel-order').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = parseInt(btn.dataset.orderId, 10);
                    const card = btn.closest('.order-card');
                    cancelOrder(id, card);
                });
            });

        } catch (e) {
            c.innerHTML = `<p class="no-orders">${accountT('Failed to load orders.')}</p>`;
        }
    }

    if (document.getElementById('ordersContainer')) loadOrders();

    const bankReceiptForm = document.getElementById('bankReceiptForm');
    if (bankReceiptForm) {
        bankReceiptForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = bankReceiptForm.querySelector('button[type="submit"]');
            const target = bankReceiptForm.querySelector('.status-line');
            const original = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${accountT('Sending')}`;
            target.innerHTML = `<i class="fas fa-circle-notch fa-spin"></i> ${accountT('Sending request...')}`;
            target.style.color = 'var(--muted)';

            try {
                const fd = new FormData(bankReceiptForm);
                const payload = {
                    action: 'receipt',
                    ...Object.fromEntries(fd.entries())
                };

                const res = await apiPost('api/feature-requests.php', payload);
                if (res.success) {
                    target.innerHTML = `<i class="fas fa-circle-check"></i> ${res.message || accountT('Done')}`;
                    target.style.color = '#00e676';
                    bankReceiptForm.reset();
                } else {
                    target.innerHTML = `<i class="fas fa-circle-xmark"></i> ${res.message || accountT('Failed')}`;
                    target.style.color = '#ff3d5a';
                }
            } catch (error) {
                target.innerHTML = `<i class="fas fa-circle-xmark"></i> ${error.message || accountT('Request failed.')}`;
                target.style.color = '#ff3d5a';
            } finally {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        });
    }

    async function loadWishlist() {
        const c = document.getElementById('wishlistContainer');
        if (!c) return;

        try {
            const res = await fetch('api/wishlist.php?details=true', { credentials: 'same-origin' });
            const data = await res.json();

            if (!data.products || !data.products.length) {
                c.innerHTML = `
                    <div style="grid-column: 1/-1; text-align:center; padding:48px 20px;">
                        <i class="far fa-heart" style="font-size:3rem; color:var(--muted); margin-bottom:16px; display:block;"></i>
                        <p class="no-orders" style="font-size:1.1rem;">${accountT('Your wishlist is empty')}</p>
                        <p style="color:var(--muted); font-size:0.88rem; margin-top:6px;">${accountT('Save items you like to view them later.')}</p>
                        <a href="products.php" style="display:inline-block; margin-top:20px; padding:12px 28px; background:var(--cyan); color:#000; border-radius:10px; font-weight:700; text-decoration:none; transition:all 0.2s;">
                            <i class="fas fa-shopping-bag"></i> ${accountT('Browse Products')}
                        </a>
                    </div>`;
                return;
            }

            c.innerHTML = data.products.map(p => {
                const discount = p.oldPrice || p.old_price 
                    ? Math.round(((parseFloat(p.oldPrice || p.old_price) - parseFloat(p.price)) / parseFloat(p.oldPrice || p.old_price)) * 100)
                    : 0;
                
                const current = formatMAD(p.price);
                const priceHtml = p.old_price 
                    ? `<span class="product-price">${current}</span><span class="product-old-price">${formatMAD(p.old_price)}</span><span class="product-discount">−${discount}%</span>`
                    : `<span class="product-price">${current}</span>`;

                return `
                    <article class="product-card">
                        <div class="product-img-wrap">
                            <img src="${p.image}" alt="${p.name}" class="product-img" loading="lazy">
                            <button class="product-wishlist active" data-id="${p.id}">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        <div class="product-card-body">
                            <p class="product-category">${(p.category || '').toUpperCase()}</p>
                            <h3 class="product-card-name">${p.name}</h3>
                            <div class="product-price-row">
                                ${priceHtml}
                            </div>
                            <div class="product-card-actions">
                                <a href="products.php" class="btn btn-primary" style="text-align: center; display: block; text-decoration: none; width: 100%;"><i class="fas fa-eye"></i> ${accountT('View')}</a>
                            </div>
                        </div>
                    </article>`;
            }).join('');

            // Bind remove button
            c.querySelectorAll('.product-wishlist').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = parseInt(btn.dataset.id);
                    try {
                        const r = await fetch('api/wishlist.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'toggle', product_id: id })
                        });
                        const d = await r.json();
                        if (d.success) {
                            if (typeof Wishlist !== 'undefined') Wishlist.init(); // sync global
                            loadWishlist(); // reload list
                        }
                    } catch (err) {}
                });
            });

        } catch (e) {
            c.innerHTML = `<p class="no-orders" style="grid-column: 1/-1;">${accountT('Failed to load wishlist.')}</p>`;
        }
    }

    if (document.getElementById('wishlistContainer')) loadWishlist();

    async function loadSavedBuilds() {
        const c = document.getElementById('savedBuildsContainer');
        if (!c) return;

        try {
            const res = await fetch('api/builder-save.php?my=1', { credentials: 'same-origin' });
            const data = await res.json();

            if (!data.success || !data.builds || !data.builds.length) {
                c.innerHTML = `
                    <div style="text-align:center;padding:48px 20px;">
                        <i class="fas fa-computer" style="font-size:3rem;color:var(--muted);margin-bottom:16px;display:block;"></i>
                        <p class="no-orders" style="font-size:1.1rem;">${accountT('No saved builds yet')}</p>
                        <p style="color:var(--muted);font-size:0.88rem;margin-top:6px;">${accountT('Use the Builder to save, share, and price full setups.')}</p>
                        <a href="builder.php" style="display:inline-block;margin-top:20px;padding:12px 28px;background:var(--cyan);color:#000;border-radius:10px;font-weight:700;text-decoration:none;">
                            <i class="fas fa-screwdriver-wrench"></i> ${accountT('Open Builder')}
                        </a>
                    </div>`;
                return;
            }

            c.innerHTML = `<div class="orders-list">${data.builds.map(build => {
                const shareUrl = `${window.location.origin}${window.location.pathname.replace(/account\.php$/, 'builder.php')}?build=${build.share_code}`;
                return `
                    <div class="order-card saved-build-card" data-build-id="${build.id}" style="grid-template-columns:1fr auto auto;">
                        <div class="order-card-left">
                            <div class="order-id">${build.build_name || accountT('Saved Build')}</div>
                            <div class="order-date">${(build.use_case || 'general').toUpperCase()} - ${new Date(build.created_at).toLocaleDateString(document.documentElement.lang || undefined)} - ${build.total_wattage || 0}W</div>
                        </div>
                        <div style="font-family:'JetBrains Mono',monospace;color:var(--cyan);font-weight:800;">${formatMAD(build.total_price || 0)}</div>
                        <div class="order-card-actions">
                            <a class="btn-view" href="builder.php?build=${encodeURIComponent(build.share_code)}"><i class="fas fa-eye"></i> ${accountT('Open')}</a>
                            <button class="btn-view share-build-btn" data-url="${shareUrl}"><i class="fas fa-share-alt"></i> ${accountT('Share')}</button>
                            <button class="btn-cancel-order delete-build-btn" data-id="${build.id}"><i class="fas fa-trash"></i> ${accountT('Delete')}</button>
                        </div>
                    </div>`;
            }).join('')}</div>`;

            c.querySelectorAll('.share-build-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(btn.dataset.url);
                        showToast('Build link copied.', 'success');
                    } catch {
                        prompt(accountT('Copy this build link:'), btn.dataset.url);
                    }
                });
            });

            c.querySelectorAll('.delete-build-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm(accountT('Delete this saved build?'))) return;
                    const r = await apiPost('api/builder-save.php', { action: 'delete', build_id: parseInt(btn.dataset.id, 10) });
                    if (r.success) {
                        showToast('Build deleted.', 'success');
                        loadSavedBuilds();
                    } else {
                        showToast(r.message || 'Failed to delete build.', 'error');
                    }
                });
            });
        } catch (e) {
            c.innerHTML = `<p class="no-orders">${accountT('Failed to load saved builds.')}</p>`;
        }
    }

    if (document.getElementById('savedBuildsContainer')) loadSavedBuilds();

    // ── Loyalty ──────────────────────────────────────────────
    async function loadLoyalty() {
        const historyEl = document.getElementById('loyaltyHistory');
        if (!historyEl) return;

        try {
            // Fetch Balance & Progress
            const balanceRes = await fetch('api/loyalty.php?action=balance');
            const b = await balanceRes.json();
            
            let currentPoints = 0;

            if (b.success) {
                currentPoints = b.balance;
                const progBar = document.getElementById('loyaltyProgressBar');
                const progLabel = document.getElementById('loyaltyProgressLabel');
                const benefitsEl = document.getElementById('loyaltyBenefits');

                if (progBar) progBar.style.width = `${b.tier_progress}%`;
                if (progLabel) {
                    progLabel.textContent = b.tier === 'platinum'
                        ? accountT('MAX TIER REACHED')
                        : accountT('{earned} / {next} PTS TO {tier}', {
                            earned: b.lifetime_earned,
                            next: b.next_tier_points,
                            tier: String(b.next_tier || '').toUpperCase()
                        });
                }
                
                if (benefitsEl && b.tier_benefits) {
                    benefitsEl.innerHTML = b.tier_benefits.map(ben => 
                        `<div style="padding:10px 14px;background:var(--page-bg);border:1px solid var(--border);border-radius:10px;font-size:0.82rem;color:var(--text);"><i class="fas fa-check" style="color:#00f5d4;margin-right:6px;"></i> ${accountT(ben)}</div>`
                    ).join('');
                }
            }

            // Fetch Catalog (Batch 2 wire-up)
            try {
                // Remove existing catalog if any to prevent duplicates on reload
                const existingCatalog = document.getElementById('loyaltyCatalogUI');
                if (existingCatalog) existingCatalog.remove();

                const catalogRes = await fetch('api/loyalty.php?action=catalog');
                const c = await catalogRes.json();
                if (c.success && c.catalog && c.catalog.length > 0) {
                    let catalogHtml = `<div id="loyaltyCatalogUI"><h3 style="margin-top:20px;margin-bottom:15px;font-size:1.1rem;"><i class="fas fa-gift" style="color:var(--cyan); margin-right:8px;"></i> ${accountT('Rewards Store')}</h3><div class="orders-list" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-bottom: 30px;">`;
                    c.catalog.forEach(reward => {
                        const affordable = currentPoints >= reward.points_required;
                        catalogHtml += `
                            <div class="order-card" style="display:flex; flex-direction:column; gap:10px; padding: 15px; border: 1px solid ${affordable ? 'var(--cyan)' : 'var(--border)'};">
                                <h4 style="margin:0;color:var(--text);">${escapeHtml(accountT(reward.title))}</h4>
                                <p style="margin:0;font-size:0.85rem;color:var(--muted);flex-grow:1;">${escapeHtml(accountT(reward.description))}</p>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                                    <span style="font-family:'JetBrains Mono',monospace; font-weight:700; color:var(--cyan);">${reward.points_required} PTS</span>
                                    <button class="btn-view redeem-reward-btn" data-id="${reward.id}" ${affordable ? '' : 'disabled'} style="background: ${affordable ? 'var(--cyan)' : 'var(--border)'}; color: ${affordable ? '#000' : 'var(--muted)'};">
                                        ${accountT('Redeem')}
                                    </button>
                                </div>
                                <span style="font-size:0.7rem; color:var(--muted); text-align:right;">${reward.stock_remaining} ${accountT('left')}</span>
                            </div>
                        `;
                    });
                    catalogHtml += `</div></div>`;
                    
                    // Insert catalog before history
                    historyEl.insertAdjacentHTML('beforebegin', catalogHtml);

                    document.querySelectorAll('.redeem-reward-btn').forEach(btn => {
                        btn.addEventListener('click', async () => {
                            if (!confirm(accountT('Are you sure you want to redeem this reward?'))) return;
                            btn.disabled = true;
                            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                            const rewardId = btn.dataset.id;
                            const r = await apiPost('api/loyalty.php', { action: 'redeem', reward_id: parseInt(rewardId, 10) });
                            if (r.success) {
                                showToast('Reward redeemed successfully!', 'success');
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                showToast(r.message || accountT('Failed to redeem reward.'), 'error');
                                btn.disabled = false;
                                btn.innerHTML = accountT('Redeem');
                            }
                        });
                    });
                }
            } catch (ce) {
                console.error("Failed to load catalog", ce);
            }

            // Fetch History
            const historyRes = await fetch('api/loyalty.php?action=history');
            const h = await historyRes.json();

            if (!h.history || !h.history.length) {
                historyEl.innerHTML = `<p class="no-orders">${accountT('No points transactions yet.')}</p>`;
                return;
            }

            historyEl.innerHTML = `
                <h3 style="margin-top:20px;margin-bottom:15px;font-size:1.1rem;"><i class="fas fa-history" style="color:var(--cyan); margin-right:8px;"></i> ${accountT('Points History')}</h3>
                <div class="orders-list">
                    ${h.history.map(t => {
                        const isGain = t.points > 0;
                        return `
                            <div class="order-card" style="grid-template-columns: 1fr 120px 120px;">
                                <div class="order-card-left">
                                    <div class="order-id" style="font-size:0.9rem;">${escapeHtml(translateLoyaltyDescription(t.description || t.source))}</div>
                                    <div class="order-date">${new Date(t.created_at).toLocaleDateString()}</div>
                                </div>
                                <div style="font-family:'JetBrains Mono',monospace; font-weight:700; color:${isGain ? '#00e676' : '#ff3d5a'}; text-align:right;">
                                    ${isGain ? '+' : ''}${t.points} PTS
                                </div>
                                <div class="order-status status-neutral" style="text-align:center; font-size:0.7rem;">${t.source.toUpperCase()}</div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;

        } catch (e) {
            historyEl.innerHTML = `<p class="no-orders">${accountT('Failed to load loyalty data.')}</p>`;
        }
    }

    if (document.getElementById('loyaltyHistory')) loadLoyalty();

    // ── Account deletion ─────────────────────────────────────
    const deleteBtn = document.getElementById('deleteAccountBtn');
    const deleteBackdrop = document.getElementById('deleteModalBackdrop');
    const deleteCancel = document.getElementById('deleteModalCancel');
    const deleteConfirm = document.getElementById('deleteModalConfirm');
    const deletePassword = document.getElementById('deleteConfirmPassword');

    function openDeleteModal() {
        if (deleteBackdrop) {
            deleteBackdrop.classList.add('is-open');
            setTimeout(() => deletePassword?.focus(), 200);
        }
    }

    function closeDeleteModal() {
        if (deleteBackdrop) {
            deleteBackdrop.classList.remove('is-open');
            if (deletePassword) deletePassword.value = '';
        }
    }

    if (deleteBtn) deleteBtn.addEventListener('click', openDeleteModal);
    if (deleteCancel) deleteCancel.addEventListener('click', closeDeleteModal);

    if (deleteBackdrop) {
        deleteBackdrop.addEventListener('click', (e) => {
            if (e.target === deleteBackdrop) closeDeleteModal();
        });
    }

    if (deleteConfirm) {
        deleteConfirm.addEventListener('click', async () => {
            const password = deletePassword?.value || '';
            if (!password) {
                showToast('Please enter your password.', 'error');
                deletePassword?.focus();
                return;
            }

            deleteConfirm.disabled = true;
            deleteConfirm.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${accountT('Deleting...')}`;

            const r = await apiPost('api/delete-account.php', { action: 'delete', password });

            if (r.success) {
                closeDeleteModal();
                showToast(r.message || accountT('Account scheduled for deletion.'), 'success');
                // Reload page to show the restore banner
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showToast(r.error || accountT('Failed to delete account.'), 'error');
                deleteConfirm.disabled = false;
                deleteConfirm.innerHTML = `<i class="fas fa-trash-alt"></i> ${accountT('Delete Account')}`;
            }
        });
    }

    // Enter key in password field triggers delete
    if (deletePassword) {
        deletePassword.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') deleteConfirm?.click();
        });
    }

    // ── Account restoration ──────────────────────────────────
    const restoreBtn = document.getElementById('restoreAccountBtn');
    if (restoreBtn) {
        restoreBtn.addEventListener('click', async () => {
            restoreBtn.disabled = true;
            restoreBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${accountT('Restoring...')}`;

            const r = await apiPost('api/delete-account.php', { action: 'restore' });

            if (r.success) {
                showToast(r.message || 'Account restored!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(r.error || accountT('Failed to restore.'), 'error');
                restoreBtn.disabled = false;
                restoreBtn.innerHTML = `<i class="fas fa-undo"></i> ${accountT('Restore')}`;
            }
        });
    }
})();

#!/bin/bash

# ============================================================
# GitHub SSH Key Setup for VPS
# ============================================================

set -e

KEY_DIR="/root/.ssh"
KEY_NAME="github_vps_ed25519"
KEY_PATH="$KEY_DIR/$KEY_NAME"
PUB_KEY="$KEY_PATH.pub"
SSH_CONFIG="$KEY_DIR/config"

echo ""
echo "============================================================"
echo "        GITHUB SSH KEY SETUP - VPS"
echo "============================================================"
echo ""

# ------------------------------------------------------------
# 1. Check SSH directory
# ------------------------------------------------------------

mkdir -p "$KEY_DIR"
chmod 700 "$KEY_DIR"

echo "[1/6] SSH directory:"
echo "      $KEY_DIR"
echo ""

# ------------------------------------------------------------
# 2. Generate SSH key
# ------------------------------------------------------------

if [ -f "$KEY_PATH" ]; then

    echo "[2/6] SSH key đã tồn tại:"
    echo "      $KEY_PATH"
    echo ""

else

    echo "[2/6] Đang tạo SSH key..."
    echo ""

    ssh-keygen \
        -t ed25519 \
        -C "github-vps-$(hostname)" \
        -f "$KEY_PATH"

    echo ""
    echo "SSH key đã được tạo."
    echo ""

fi

chmod 600 "$KEY_PATH"
chmod 644 "$PUB_KEY"

# ------------------------------------------------------------
# 3. Configure SSH
# ------------------------------------------------------------

echo "[3/6] Kiểm tra SSH config..."

touch "$SSH_CONFIG"
chmod 600 "$SSH_CONFIG"

if ! grep -q "Host github.com" "$SSH_CONFIG"; then

cat >> "$SSH_CONFIG" <<EOF

# GitHub VPS
Host github.com
    HostName github.com
    User git
    IdentityFile $KEY_PATH
    IdentitiesOnly yes

EOF

    echo "Đã thêm cấu hình GitHub vào:"
    echo "      $SSH_CONFIG"

else

    echo "SSH config GitHub đã tồn tại."

fi

echo ""

# ------------------------------------------------------------
# 4. Show public key
# ------------------------------------------------------------

echo "[4/6] PUBLIC KEY CỦA VPS"
echo ""
echo "------------------------------------------------------------"
cat "$PUB_KEY"
echo "------------------------------------------------------------"
echo ""

# ------------------------------------------------------------
# 5. GitHub instructions
# ------------------------------------------------------------

echo "============================================================"
echo " HƯỚNG DẪN ADD KEY VÀO GITHUB"
echo "============================================================"
echo ""
echo "Bước 1:"
echo "Mở GitHub và đăng nhập tài khoản của bạn."
echo ""
echo "Bước 2:"
echo "Vào:"
echo "GitHub -> Settings -> SSH and GPG keys"
echo ""
echo "Bước 3:"
echo "Chọn:"
echo "New SSH key"
echo ""
echo "Bước 4:"
echo "Title:"
echo "VPS-$(hostname)"
echo ""
echo "Bước 5:"
echo "Key type:"
echo "Authentication Key"
echo ""
echo "Bước 6:"
echo "Copy TOÀN BỘ dòng PUBLIC KEY ở phía trên"
echo "và dán vào ô Key trên GitHub."
echo ""
echo "============================================================"
echo ""

# ------------------------------------------------------------
# 6. Test GitHub
# ------------------------------------------------------------

echo "[5/6] Kiểm tra kết nối SSH tới GitHub..."
echo ""

echo "Sau khi Add SSH Key vào GitHub,"
echo "hãy chạy:"
echo ""
echo "    ssh -T git@github.com"
echo ""

echo "Kết quả thành công sẽ tương tự:"
echo ""
echo "    Hi USERNAME! You've successfully authenticated..."
echo ""

echo "============================================================"
echo " CÁC LỆNH GIT SAU KHI SSH HOẠT ĐỘNG"
echo "============================================================"
echo ""

echo "Kiểm tra remote:"
echo ""
echo "    git remote -v"
echo ""

echo "Đổi remote:"
echo ""
echo "    git remote set-url origin git@github.com:USER/REPOSITORY.git"
echo ""

echo "Pull:"
echo ""
echo "    git pull origin main"
echo ""

echo "Push:"
echo ""
echo "    git push origin main"
echo ""

echo "============================================================"
echo " PUBLIC KEY FILE"
echo "============================================================"
echo ""
echo "$PUB_KEY"
echo ""

echo "Có thể xem lại bằng:"
echo ""
echo "    cat $PUB_KEY"
echo ""

echo "============================================================"
echo " HOÀN TẤT"
echo "============================================================"
echo ""

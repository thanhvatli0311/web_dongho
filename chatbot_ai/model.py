# Bắt đầu file model.py
import mysql.connector
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import SGDClassifier
from sklearn.pipeline import Pipeline

# Biến toàn cục để lưu model
model = None 

# === THÔNG TIN KẾT NỐI CSDL ===
# --> Hãy đảm bảo thông tin này chính xác
db_config = {
    'host': 'localhost',
    'user': 'root', # User CSDL của bạn
    'password': '', # Mật khẩu CSDL của bạn
    'database': 'dbdongho' # Tên CSDL của bạn
}

def load_data_from_db():
    """Hàm lấy dữ liệu huấn luyện từ CSDL."""
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        query = """
            SELECT tp.phrase_text, i.name 
            FROM training_phrases AS tp
            JOIN intents AS i ON tp.intent_id = i.id
        """
        cursor.execute(query)
        
        training_data = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        if not training_data:
            return [], []

        X_train = [item[0] for item in training_data]
        y_train = [item[1] for item in training_data]
        return X_train, y_train

    except mysql.connector.Error as err:
        print(f"Lỗi CSDL: {err}")
        return [], []

def train_model(): # <--- ĐÂY LÀ HÀM BỊ THIẾU
    """Hàm huấn luyện lại model từ đầu."""
    global model
    print("Bắt đầu quá trình huấn luyện từ CSDL...")
    
    X_train, y_train = load_data_from_db()
    
    if not X_train:
        print("CẢNH BÁO: Không có dữ liệu huấn luyện trong CSDL. Chatbot sẽ không thể hiểu được ý định.")
        model = None
        return

    model_pipeline = Pipeline([
        ('vectorizer', TfidfVectorizer()),
        ('classifier', SGDClassifier(loss='hinge', penalty='l2', alpha=1e-3, random_state=42, max_iter=5, tol=None)),
    ])
    
    model_pipeline.fit(X_train, y_train)
    model = model_pipeline
    print("✅ Huấn luyện hoàn tất!")

def predict_intent(user_message):
    """Dự đoán intent từ tin nhắn người dùng."""
    if model is None:
        print("Lỗi: Model chưa được huấn luyện.")
        return "#NO_MODEL"

    user_message = user_message.lower()
    predicted_intent = model.predict([user_message])[0]
    confidence_scores = model.decision_function([user_message])
    max_score = confidence_scores.max()

    print(f"Message: '{user_message}', Predicted Intent: '{predicted_intent}', Score: {max_score}")

    if max_score < 0.2:
        return "#KHONG_HIEU"
        
    return predicted_intent
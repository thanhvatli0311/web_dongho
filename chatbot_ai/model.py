import mysql.connector
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import SGDClassifier
from sklearn.pipeline import Pipeline

model = None

# Cấu hình kết nối DB giống PHP
db_config = {
    'host': 'localhost',
    'user': 'root', 
    'password': '',
    'database': 'dbdongho'
}

def load_data_from_db():
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        cursor.execute("SELECT tp.phrase_text, i.name FROM training_phrases AS tp JOIN intents AS i ON tp.intent_id = i.id")
        training_data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        if not training_data: return [], []
        return [item[0] for item in training_data], [item[1] for item in training_data]
    except Exception as e:
        print("Lỗi DB:", e)
        return [], []

def train_model():
    global model
    print("Đang huấn luyện model...")
    X_train, y_train = load_data_from_db()
    if not X_train:
        print("Không có dữ liệu huấn luyện.")
        model = None
        return
    
    model_pipeline = Pipeline([
        ('vectorizer', TfidfVectorizer()),
        ('classifier', SGDClassifier(loss='hinge', penalty='l2', alpha=1e-3, random_state=42, max_iter=5, tol=None)),
    ])
    model_pipeline.fit(X_train, y_train)
    model = model_pipeline
    print("Huấn luyện xong!")

def predict_intent(user_message):
    if model is None:
        print("Lỗi: Model chưa được huấn luyện.")
        return "#NO_MODEL"

    user_message = user_message.lower()
    predicted_intent = model.predict([user_message])[0]
    
    # Lấy điểm số cao nhất
    confidence_scores = model.decision_function([user_message])
    max_score = confidence_scores.max()

    print(f"Message: '{user_message}', Intent: '{predicted_intent}', Score: {max_score}")

    if max_score < -0.2: 
        return "#KHONG_HIEU"
        
    return predicted_intent
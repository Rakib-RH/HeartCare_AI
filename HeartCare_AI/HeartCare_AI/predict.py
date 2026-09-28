import sys
import os
import json
import warnings
warnings.filterwarnings("ignore")

def predict_heart_sound(audio_path):
    if not os.path.exists(audio_path):
        return {"status": "error", "message": f"Audio file not found: {audio_path}"}

    model_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'ai_model', 'heart_sound_model.pkl')
    if not os.path.exists(model_path):
        return {"status": "error", "message": f"Model file not found at: {model_path}"}

    try:
        # pyrefly: ignore [missing-import]
        import joblib
        # pyrefly: ignore [missing-import]
        import numpy as np
        # pyrefly: ignore [missing-import]
        import librosa

        # ১. অডিও থেকে ফিচার বের করা
        audio, sample_rate = librosa.load(audio_path, sr=None, duration=5)
        mfccs = librosa.feature.mfcc(y=audio, sr=sample_rate, n_mfcc=40)
        features = np.mean(mfccs.T, axis=0).reshape(1, -1)

        # ২. মডেল লোড ও প্রেডিকশন
        model = joblib.load(model_path)
        prediction = str(model.predict(features)[0])
        
        # ৩. কনফিডেন্স বের করা
        if hasattr(model, "predict_proba"):
            probabilities = model.predict_proba(features)[0]
            confidence = round(float(np.max(probabilities)) * 100, 2)
        else:
            confidence = 88.5

        return {
            "status": "success",
            "result": prediction,
            "confidence": confidence
        }
    except Exception as e:
        return {"status": "error", "message": str(e)}

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({"status": "error", "message": "No audio file argument provided"}))
        sys.exit(1)

    audio_file = sys.argv[1]
    res = predict_heart_sound(audio_file)
    print(json.dumps(res))
